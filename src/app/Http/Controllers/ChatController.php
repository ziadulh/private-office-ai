<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\AI\OllamaClientService;
use App\Services\AI\RagService;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        private RagService $ragService,
        private OllamaClientService $ollama,
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $sessions = ChatSession::where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get();

        $activeSession = null;
        if ($uuid = $request->query('session')) {
            $activeSession = ChatSession::with('messages')
                ->where('user_id', $request->user()->id)
                ->where('uuid', $uuid)
                ->first();
        } elseif (!$request->boolean('new') && $sessions->isNotEmpty()) {
            $activeSession = $sessions->first()->load('messages');
        }

        $ollamaOnline = $this->ollama->isHealthy();

        return view('chat.index', compact('sessions', 'activeSession', 'ollamaOnline'));
    }

    public function ask(Request $request): RedirectResponse|JsonResponse
    {
        set_time_limit(600);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'session_uuid' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $question = trim($validated['question']);
        $isNewSession = false;

        $session = null;
        if (!empty($validated['session_uuid'])) {
            $session = ChatSession::where('user_id', $user->id)
                ->where('uuid', $validated['session_uuid'])
                ->first();
        }

        if (!$session) {
            $isNewSession = true;
            $session = ChatSession::create([
                'user_id' => $user->id,
                'title' => Str::limit($question, 55),
            ]);
        }

        $history = $session->messages()
            ->latest('id')
            ->take(4)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();

        $session->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);

        try {
            $result = $this->ragService->ask($user, $question, $history);

            $assistantMsg = $session->messages()->create([
                'role' => 'assistant',
                'content' => $result['answer'],
                'citations' => $result['citations'],
            ]);

            $session->touch();

            $this->auditService->log('ai.query', $session, [
                'sources_used' => count($result['citations']),
            ]);
        } catch (\Throwable $e) {
            $assistantMsg = $session->messages()->create([
                'role' => 'assistant',
                'content' => 'AI Engine Error: ' . $e->getMessage(),
                'citations' => [],
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'is_new_session' => $isNewSession,
                'session_uuid' => $session->uuid,
                'session_title' => $session->title,
                'message' => [
                    'id' => $assistantMsg->id,
                    'content' => $assistantMsg->content,
                    'citations' => $assistantMsg->citations ?? [],
                ],
            ]);
        }

        return redirect()->route('chat.index', ['session' => $session->uuid]);
    }

    public function feedback(Request $request, ChatMessage $message): RedirectResponse|JsonResponse
    {
        if ($message->session->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'feedback' => ['required', 'in:helpful,not_helpful'],
        ]);

        $message->update(['feedback' => $validated['feedback']]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('success', 'Feedback recorded. Thank you!');
    }
}

@extends('layouts.app')

@section('title', 'AI Knowledge Assistant — Private Office AI')
@section('header', 'Private Office AI Assistant')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-10rem)]">
    <!-- Left Sidebar: Chat History -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Conversations</span>
            <a href="{{ route('chat.index', ['new' => 1]) }}"
               class="text-xs px-2.5 py-1 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                + New Chat
            </a>
        </div>

        <div id="session-list" class="flex-1 overflow-y-auto p-3 space-y-1">
            @forelse($sessions as $s)
                <a href="{{ route('chat.index', ['session' => $s->uuid]) }}"
                   class="block px-3 py-2.5 rounded-lg text-sm truncate {{ $activeSession?->id === $s->id && !request()->boolean('new') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $s->title }}
                </a>
            @empty
                <p id="no-sessions-msg" class="text-xs text-slate-400 p-3">No previous conversations.</p>
            @endforelse
        </div>

        <div class="p-3.5 border-t border-slate-100 bg-slate-50 text-xs">
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Ollama Engine:</span>
                <span class="font-semibold {{ $ollamaOnline ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $ollamaOnline ? 'Online (' . config('ai.chat_model') . ')' : 'Offline' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Right Main Panel: Messages & Prompt Input -->
    <div class="lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
        <div id="chat-feed" class="flex-1 overflow-y-auto p-6 space-y-6">
            @php
                $showMessages = $activeSession && !request()->boolean('new') ? $activeSession->messages : collect();
            @endphp

            @if($showMessages->isEmpty())
                <div id="empty-state" class="h-full flex flex-col items-center justify-center text-center max-w-md mx-auto">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl mb-4">AI</div>
                    <h3 class="text-base font-semibold text-slate-800">Ask anything from your authorized office documents</h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Supports Bengali and English. Answers are strictly grounded in documents you have permission to access, complete with source citations.
                    </p>
                </div>
            @else
                @foreach($showMessages as $msg)
                    @if($msg->role === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-2xl bg-indigo-600 text-white px-4 py-3 rounded-2xl rounded-br-sm text-sm shadow-sm">
                                {{ $msg->content }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-3xl bg-slate-50 border border-slate-200 px-5 py-4 rounded-2xl rounded-bl-sm text-sm space-y-3">
                                <div class="text-slate-800 whitespace-pre-line leading-relaxed">{{ $msg->content }}</div>

                                @if(!empty($msg->citations))
                                    <div class="pt-3 border-t border-slate-200">
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Verified Sources</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($msg->citations as $cite)
                                                <a href="{{ route('documents.show', $cite['document_id']) }}"
                                                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs text-indigo-700 hover:border-indigo-300 shadow-2xs">
                                                    <span class="font-bold">[{{ $cite['ref'] }}]</span>
                                                    <span class="truncate max-w-[200px]">{{ $cite['title'] }}</span>
                                                    <span class="text-slate-400">&bull; Pg {{ $cite['page'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="pt-1 flex items-center gap-3 text-xs text-slate-400">
                                    <span>Helpful?</span>
                                    <form method="POST" action="{{ route('chat.feedback', $msg) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="feedback" value="helpful">
                                        <button type="submit" class="{{ $msg->feedback === 'helpful' ? 'text-emerald-600 font-bold' : 'hover:text-slate-700' }}">Yes</button>
                                    </form>
                                    <form method="POST" action="{{ route('chat.feedback', $msg) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="feedback" value="not_helpful">
                                        <button type="submit" class="{{ $msg->feedback === 'not_helpful' ? 'text-rose-600 font-bold' : 'hover:text-slate-700' }}">No</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        <form id="chat-form" method="POST" action="{{ route('chat.ask') }}" class="p-4 border-t border-slate-200 bg-slate-50 flex gap-3">
            @csrf
            <input type="hidden" id="session-uuid-input" name="session_uuid"
                   value="{{ ($activeSession && !request()->boolean('new')) ? $activeSession->uuid : '' }}">
            <input type="text" id="question-input" name="question" required autocomplete="off"
                   placeholder="Ask a question in Bengali or English..."
                   class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="submit" id="ask-btn"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl shadow-sm transition">
                Ask AI
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chat-form');
    const feed = document.getElementById('chat-feed');
    const input = document.getElementById('question-input');
    const btn = document.getElementById('ask-btn');
    const sessionInput = document.getElementById('session-uuid-input');
    const sessionList = document.getElementById('session-list');
    const csrfToken = form.querySelector('input[name="_token"]').value;

    const scrollToBottom = () => {
        if (feed) feed.scrollTop = feed.scrollHeight;
    };

    const escapeHtml = (str) => String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    scrollToBottom();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const question = input.value.trim();
        if (!question) return;

        const emptyState = document.getElementById('empty-state');
        if (emptyState) emptyState.remove();

        // 1. Append User Bubble immediately
        const userDiv = document.createElement('div');
        userDiv.className = 'flex justify-end';
        userDiv.innerHTML = `<div class="max-w-2xl bg-indigo-600 text-white px-4 py-3 rounded-2xl rounded-br-sm text-sm shadow-sm">${escapeHtml(question)}</div>`;
        feed.appendChild(userDiv);

        // 2. Append Thinking Bubble
        const thinkingDiv = document.createElement('div');
        thinkingDiv.className = 'flex justify-start';
        thinkingDiv.innerHTML = `<div class="bg-slate-100 border border-slate-200 px-4 py-3 rounded-2xl text-xs text-slate-600 animate-pulse">AI is reading authorized documents and generating answer...</div>`;
        feed.appendChild(thinkingDiv);
        scrollToBottom();

        input.value = '';
        input.disabled = true;
        btn.disabled = true;
        btn.textContent = 'Thinking...';

        try {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('question', question);
            if (sessionInput.value) {
                formData.append('session_uuid', sessionInput.value);
            }

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (!response.ok) throw new Error('Server error: ' + response.status);
            const data = await response.json();

            // Update active session UUID without reloading page
            sessionInput.value = data.session_uuid;
            window.history.replaceState({}, '', `{{ route('chat.index') }}?session=${data.session_uuid}`);

            // Add new conversation to left sidebar if newly created
            if (data.is_new_session && sessionList) {
                const noMsg = document.getElementById('no-sessions-msg');
                if (noMsg) noMsg.remove();
                sessionList.querySelectorAll('a').forEach(a => {
                    a.className = 'block px-3 py-2.5 rounded-lg text-sm truncate text-slate-600 hover:bg-slate-50';
                });
                const newLink = document.createElement('a');
                newLink.href = `{{ route('chat.index') }}?session=${data.session_uuid}`;
                newLink.className = 'block px-3 py-2.5 rounded-lg text-sm truncate bg-indigo-50 text-indigo-700 font-medium';
                newLink.textContent = data.session_title;
                sessionList.prepend(newLink);
            }

            // Build Citations HTML
            let citationsHtml = '';
            if (data.message.citations && data.message.citations.length > 0) {
                const badges = data.message.citations.map(c => `
                    <a href="/documents/${c.document_id}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs text-indigo-700 hover:border-indigo-300 shadow-2xs">
                        <span class="font-bold">[${c.ref}]</span>
                        <span class="truncate max-w-[200px]">${escapeHtml(c.title)}</span>
                        <span class="text-slate-400">&bull; Pg ${c.page}</span>
                    </a>
                `).join('');

                citationsHtml = `
                    <div class="pt-3 border-t border-slate-200">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Verified Sources</div>
                        <div class="flex flex-wrap gap-2">${badges}</div>
                    </div>
                `;
            }

            // Replace Thinking Bubble with Assistant Answer (No Page Reload!)
            thinkingDiv.innerHTML = `
                <div class="max-w-3xl bg-slate-50 border border-slate-200 px-5 py-4 rounded-2xl rounded-bl-sm text-sm space-y-3">
                    <div class="text-slate-800 whitespace-pre-line leading-relaxed">${escapeHtml(data.message.content)}</div>
                    ${citationsHtml}
                    <div class="pt-1 flex items-center gap-3 text-xs text-slate-400">
                        <span>Helpful?</span>
                        <form method="POST" action="/assistant/messages/${data.message.id}/feedback" class="inline">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="feedback" value="helpful">
                            <button type="submit" class="hover:text-slate-700">Yes</button>
                        </form>
                        <form method="POST" action="/assistant/messages/${data.message.id}/feedback" class="inline">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="feedback" value="not_helpful">
                            <button type="submit" class="hover:text-slate-700">No</button>
                        </form>
                    </div>
                </div>
            `;
        } catch (error) {
            thinkingDiv.innerHTML = `
                <div class="bg-rose-50 border border-rose-200 px-4 py-3 rounded-2xl text-xs text-rose-700">
                    Failed to receive response (${escapeHtml(error.message)}). Please try again.
                </div>
            `;
        } finally {
            input.disabled = false;
            btn.disabled = false;
            btn.textContent = 'Ask AI';
            input.focus();
            scrollToBottom();
        }
    });
});
</script>
@endsection

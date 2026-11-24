<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = $this->documentService->accessibleDocumentsQuery($request->user());

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($deptId = $request->input('department_id')) {
            $query->where('department_id', $deptId);
        }

        if ($catId = $request->input('category_id')) {
            $query->where('category_id', $catId);
        }

        if ($conf = $request->input('confidentiality')) {
            $query->where('confidentiality', $conf);
        }

        $documents = $query->withCount('chunks')->latest()->paginate(12)->withQueryString();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $categories = DocumentCategory::where('is_active', true)->orderBy('name')->get();

        return view('documents.index', compact('documents', 'departments', 'categories'));
    }

    public function create(): View
    {
        return view('documents.create', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'categories' => DocumentCategory::where('is_active', true)->orderBy('name')->get(),
            'users' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'file' => ['required', 'file', 'mimes:pdf,docx,txt,xlsx,csv', 'max:25600'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'category_id' => ['nullable', 'exists:document_categories,id'],
            'confidentiality' => ['required', 'in:public_internal,department_restricted,confidential,highly_confidential'],
            'description' => ['nullable', 'string', 'max:1000'],
            'grant_user_ids' => ['nullable', 'array'],
            'grant_user_ids.*' => ['exists:users,id'],
        ]);

        $result = $this->documentService->upload($request->file('file'), $validated, $request->user());

        $msg = 'Document uploaded and queued for AI text extraction.';
        if ($result['is_duplicate']) {
            $msg .= ' Note: An identical file checksum already exists in the vault.';
        }

        return redirect()->route('documents.show', $result['document'])->with('success', $msg);
    }

    public function show(Document $document): View
    {
        if (Gate::denies('view', $document)) {
            $this->auditService->log('document.view_denied', $document);
            abort(403, 'You are not authorized to view this document.');
        }

        $document->load(['department', 'category', 'uploader'])->loadCount('chunks');
        $sampleChunks = $document->chunks()->take(3)->get();

        return view('documents.show', compact('document', 'sampleChunks'));
    }

    public function reprocess(Document $document): RedirectResponse
    {
        Gate::authorize('view', $document);

        $document->update(['status' => 'pending', 'processing_error' => null]);
        ProcessDocumentJob::dispatch($document->id);

        return back()->with('success', 'Document text extraction queued for reprocessing.');
    }

    public function download(Document $document): StreamedResponse
    {
        if (Gate::denies('download', $document)) {
            $this->auditService->log('document.download_denied', $document);
            abort(403, 'Unauthorized download attempt logged.');
        }

        if (!Storage::disk('local')->exists($document->storage_path)) {
            abort(404, 'File missing from private storage vault.');
        }

        $this->auditService->log('document.downloaded', $document, ['title' => $document->title]);

        return Storage::disk('local')->download($document->storage_path, $document->original_filename);
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $this->documentService->delete($document);

        return redirect()->route('documents.index')->with('success', 'Document permanently deleted from vault.');
    }
}

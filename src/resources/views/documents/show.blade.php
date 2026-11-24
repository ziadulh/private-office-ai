@extends('layouts.app')

@section('title', $document->title . ' — Private Office AI')
@section('header', 'Document Metadata & AI Extraction Status')

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-wrap justify-between items-start gap-4 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $document->title }}</h2>
                <p class="text-xs font-mono text-slate-400 mt-1">UUID: {{ $document->uuid }}</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('documents.reprocess', $document) }}">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">
                        Reprocess Text
                    </button>
                </form>
                @can('download', $document)
                    <a href="{{ route('documents.download', $document) }}"
                       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                        Download File
                    </a>
                @endcan
                @can('delete', $document)
                    <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Permanently delete this document?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-sm font-medium rounded-lg">
                            Delete
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-slate-400 block text-xs">Original Filename</span>
                <span class="font-medium text-slate-800">{{ $document->original_filename }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">Confidentiality</span>
                <span class="font-semibold text-indigo-600">{{ str_replace('_', ' ', ucfirst($document->confidentiality)) }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">AI Parsing Status</span>
                <span class="font-semibold {{ $document->status === 'completed' ? 'text-emerald-600' : ($document->status === 'failed' ? 'text-rose-600' : 'text-amber-600') }}">
                    {{ strtoupper($document->status) }} ({{ $document->chunks_count }} chunks)
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">Department</span>
                <span class="font-medium text-slate-800">{{ $document->department?->name ?? 'Company-Wide' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">Category</span>
                <span class="font-medium text-slate-800">{{ $document->category?->name ?? 'Uncategorized' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">File Size</span>
                <span class="font-medium text-slate-800">{{ $document->formatted_size }}</span>
            </div>
            <div class="md:col-span-3">
                <span class="text-slate-400 block text-xs">SHA-256 Integrity Checksum</span>
                <span class="font-mono text-xs text-slate-600 break-all">{{ $document->checksum }}</span>
            </div>
        </div>

        @if($document->processing_error)
            <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <strong>Extraction Error:</strong> {{ $document->processing_error }}
            </div>
        @endif

        @if($document->description)
            <div class="pt-4 border-t border-slate-100">
                <span class="text-slate-400 block text-xs mb-1">Description</span>
                <p class="text-sm text-slate-700">{{ $document->description }}</p>
            </div>
        @endif
    </div>

    <!-- Extracted Text Chunks Preview -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Extracted Knowledge Chunks Preview (Ready for RAG)</h3>
        @if($sampleChunks->isEmpty())
            <p class="text-sm text-slate-400">No text chunks extracted yet. Run the queue worker or click "Reprocess Text".</p>
        @else
            <div class="space-y-3">
                @foreach($sampleChunks as $chunk)
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                        <div class="flex justify-between text-slate-400 font-mono mb-1">
                            <span>Chunk #{{ $chunk->chunk_index + 1 }} • Page {{ $chunk->page_number ?? 'N/A' }}</span>
                            <span>~{{ $chunk->token_estimate }} tokens</span>
                        </div>
                        <p class="text-slate-700 whitespace-pre-line leading-relaxed">{{ $chunk->content }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <a href="{{ route('documents.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to Document Vault</a>
    </div>
</div>
@endsection

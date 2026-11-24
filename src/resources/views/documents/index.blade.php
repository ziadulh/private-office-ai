@extends('layouts.app')

@section('title', 'Documents Vault — Private Office AI')
@section('header', 'Knowledge Base & Document Vault')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <p class="text-sm text-slate-500">All documents are encrypted at rest inside private server storage with strict RBAC enforcement.</p>
    @if(auth()->user()->hasPermission('documents.upload'))
        <a href="{{ route('documents.create') }}"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm shrink-0">
            + Upload Document
        </a>
    @endif
</div>

<!-- Search & Filter Bar -->
<form method="GET" action="{{ route('documents.index') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or filename..."
           class="px-3.5 py-2 rounded-lg border border-slate-300 text-sm">

    <select name="department_id" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
            <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
        @endforeach
    </select>

    <select name="category_id" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>

    <select name="confidentiality" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">All Clearance Levels</option>
        <option value="public_internal" @selected(request('confidentiality') === 'public_internal')>Public Internal</option>
        <option value="department_restricted" @selected(request('confidentiality') === 'department_restricted')>Department Restricted</option>
        <option value="confidential" @selected(request('confidentiality') === 'confidential')>Confidential</option>
        <option value="highly_confidential" @selected(request('confidentiality') === 'highly_confidential')>Highly Confidential</option>
    </select>

    <div class="flex gap-2">
        <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-lg">
            Filter
        </button>
        <a href="{{ route('documents.index') }}" class="px-3 py-2 border border-slate-300 text-slate-600 text-sm rounded-lg hover:bg-slate-50">
            Reset
        </a>
    </div>
</form>

<!-- Documents Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
        <tr>
            <th class="px-6 py-3.5">Document Title</th>
            <th class="px-6 py-3.5">Department & Category</th>
            <th class="px-6 py-3.5">Confidentiality</th>
            <th class="px-6 py-3.5">Size</th>
            <th class="px-6 py-3.5">Uploaded By</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($documents as $doc)
            @php
                $badgeColors = [
                    'public_internal' => 'bg-emerald-50 text-emerald-700',
                    'department_restricted' => 'bg-sky-50 text-sky-700',
                    'confidential' => 'bg-amber-50 text-amber-700',
                    'highly_confidential' => 'bg-rose-50 text-rose-700',
                ];
            @endphp
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4">
                    <a href="{{ route('documents.show', $doc) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                        {{ $doc->title }}
                    </a>
                    <div class="text-xs text-slate-400">{{ $doc->original_filename }}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-slate-700">{{ $doc->department?->name ?? 'Company-Wide' }}</div>
                    <div class="text-xs text-slate-400">{{ $doc->category?->name ?? 'Uncategorized' }}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $badgeColors[$doc->confidentiality] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ str_replace('_', ' ', ucfirst($doc->confidentiality)) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-xs font-mono text-slate-500">{{ $doc->formatted_size }}</td>
                <td class="px-6 py-4">
                    <div class="text-slate-700 text-xs font-medium">{{ $doc->uploader?->name }}</div>
                    <div class="text-[11px] text-slate-400">{{ $doc->created_at->format('M d, Y') }}</div>
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                    <a href="{{ route('documents.show', $doc) }}" class="text-slate-600 hover:text-slate-900">Details</a>
                    @can('download', $doc)
                        <a href="{{ route('documents.download', $doc) }}" class="text-indigo-600 hover:underline font-medium">Download</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                    No authorized documents found in the vault yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @if($documents->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $documents->links() }}
        </div>
    @endif
</div>
@endsection

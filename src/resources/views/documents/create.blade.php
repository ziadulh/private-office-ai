@extends('layouts.app')

@section('title', 'Upload Document — Private Office AI')
@section('header', 'Upload Document to Private Vault')

@section('content')
<div class="max-w-3xl bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Document Title *</label>
            <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Employee Leave & Attendance Policy 2026" required
                   class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Select File (PDF, DOCX, TXT, XLSX, CSV — Max 25MB) *</label>
            <input type="file" name="file" accept=".pdf,.docx,.txt,.xlsx,.csv" required
                   class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm bg-slate-50">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                <select name="department_id" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                    <option value="">-- Company-Wide --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                <select name="category_id" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                    <option value="">-- Select Category --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confidentiality Level *</label>
                <select name="confidentiality" required class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                    <option value="public_internal" @selected(old('confidentiality') === 'public_internal')>Public Internal</option>
                    <option value="department_restricted" @selected(old('confidentiality', 'department_restricted') === 'department_restricted')>Department Restricted</option>
                    <option value="confidential" @selected(old('confidentiality') === 'confidential')>Confidential</option>
                    <option value="highly_confidential" @selected(old('confidentiality') === 'highly_confidential')>Highly Confidential</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Grant Specific Employee Access (Optional)</label>
            <select name="grant_user_ids[]" multiple class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm h-28">
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1">Hold Ctrl (or Cmd) to select multiple employees who can explicitly access this file.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Summary / Description</label>
            <textarea name="description" rows="3" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                Upload to Vault
            </button>
            <a href="{{ route('documents.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
</div>
@endsection

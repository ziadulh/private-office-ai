@extends('layouts.app')

@section('title', ($department->exists ? 'Edit' : 'Create') . ' Department — Private Office AI')
@section('header', $department->exists ? 'Edit Department: ' . $department->name : 'Create New Department')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}" class="space-y-5">
        @csrf
        @if($department->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Department Name *</label>
                <input type="text" name="name" value="{{ old('name', $department->name) }}" required
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Code *</label>
                <input type="text" name="code" value="{{ old('code', $department->code) }}" placeholder="e.g. MKT" required
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm uppercase">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Department Manager</label>
            <select name="manager_id" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                <option value="">-- No Manager Assigned --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('manager_id', $department->manager_id) == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">{{ old('description', $department->description) }}</textarea>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department->is_active)) class="rounded border-slate-300 text-indigo-600">
                <span>Department is active</span>
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                {{ $department->exists ? 'Update Department' : 'Create Department' }}
            </button>
            <a href="{{ route('departments.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
</div>
@endsection

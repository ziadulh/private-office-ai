@extends('layouts.app')

@section('title', ($role->exists ? 'Edit' : 'Create') . ' Role — Private Office AI')
@section('header', $role->exists ? 'Configure Role: ' . $role->name : 'Create New Role')

@section('content')
<div class="max-w-4xl bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}" class="space-y-6">
        @csrf
        @if($role->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Role Name *</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}"
                       {{ $role->slug === 'super-admin' ? 'readonly' : 'required' }}
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm {{ $role->slug === 'super-admin' ? 'bg-slate-100' : '' }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description', $role->description) }}"
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
        </div>

        @if($role->slug === 'super-admin')
            <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                Super Admin inherently possesses all system permissions and cannot be restricted.
            </div>
        @else
            <div>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Assign Permissions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($permissionGroups as $group =>$permissions)
                        <div class="p-4 rounded-lg border border-slate-200 bg-slate-50/50">
                            <div class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-2.5">{{ $group }}</div>
                            <div class="space-y-2">
                                @foreach($permissions as$perm)
                                    <label class="flex items-start gap-2.5 text-sm text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                               @checked(in_array($perm->id, old('permissions',$assignedPermissions)))
                                               class="mt-0.5 rounded border-slate-300 text-indigo-600">
                                        <div>
                                            <div class="font-medium leading-tight">{{ $perm->name }}</div>
                                            <div class="text-[11px] font-mono text-slate-400">{{ $perm->slug }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                {{ $role->exists ? 'Save Role & Permissions' : 'Create Role' }}
            </button>
            <a href="{{ route('roles.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Roles & Permissions — Private Office AI')
@section('header', 'Role & Permission Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <p class="text-sm text-slate-500">Define access levels and assign granular system permissions to roles.</p>
    <a href="{{ route('roles.create') }}"
       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm">
        + New Role
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
        <tr>
            <th class="px-6 py-3.5">Role Name</th>
            <th class="px-6 py-3.5">Slug</th>
            <th class="px-6 py-3.5">Description</th>
            <th class="px-6 py-3.5">Users</th>
            <th class="px-6 py-3.5">Permissions</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($roles as $role)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-semibold text-slate-900">{{ $role->name }}</td>
                <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $role->slug }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $role->description }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $role->users_count }}</td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-indigo-50 text-indigo-700 font-medium">
                        {{ $role->slug === 'super-admin' ? 'All (Unrestricted)' : $role->permissions_count . ' permissions' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('roles.edit', $role) }}" class="text-indigo-600 hover:underline font-medium">
                        Configure Permissions
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

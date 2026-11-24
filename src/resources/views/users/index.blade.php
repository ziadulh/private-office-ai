@extends('layouts.app')

@section('title', 'Users — Private Office AI')
@section('header', 'Employee & User Management')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <p class="text-sm text-slate-500">Manage internal employee accounts, role assignments, and department access.</p>
    @if(auth()->user()->hasPermission('users.create'))
        <a href="{{ route('users.create') }}"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm shrink-0">
            + Add Employee
        </a>
    @endif
</div>

<!-- Search & Filter Bar -->
<form method="GET" action="{{ route('users.index') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, or ID..."
           class="md:col-span-2 px-3.5 py-2 rounded-lg border border-slate-300 text-sm">

    <select name="department_id" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
            <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
        @endforeach
    </select>

    <select name="role_id" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">All Roles</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->name }}</option>
        @endforeach
    </select>

    <div class="flex gap-2">
        <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-lg">
            Filter
        </button>
        <a href="{{ route('users.index') }}" class="px-3 py-2 border border-slate-300 text-slate-600 text-sm rounded-lg hover:bg-slate-50">
            Reset
        </a>
    </div>
</form>

<!-- Users Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
        <tr>
            <th class="px-6 py-3.5">Emp ID</th>
            <th class="px-6 py-3.5">Employee</th>
            <th class="px-6 py-3.5">Department</th>
            <th class="px-6 py-3.5">Roles</th>
            <th class="px-6 py-3.5">Status</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($users as $u)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $u->employee_id ?? '—' }}</td>
                <td class="px-6 py-4">
                    <div class="font-medium text-slate-900">{{ $u->name }}</div>
                    <div class="text-xs text-slate-400">{{ $u->email }} {{ $u->designation ? '• ' . $u->designation : '' }}</div>
                </td>
                <td class="px-6 py-4 text-slate-600">{{ $u->department?->name ?? 'Unassigned' }}</td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">
                        @foreach($u->roles as $r)
                            <span class="px-2 py-0.5 text-xs rounded bg-indigo-50 text-indigo-700 font-medium">{{ $r->name }}</span>
                        @endforeach
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-0.5 text-xs rounded-full {{ $u->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                    @if(auth()->user()->hasPermission('users.edit'))
                        <a href="{{ route('users.edit', $u) }}" class="text-indigo-600 hover:underline font-medium">Edit</a>
                    @endif
                    @if(auth()->user()->hasPermission('users.status') && $u->id !== auth()->id())
                        <form action="{{ route('users.toggle', $u) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-slate-500 hover:text-slate-900">
                                {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-slate-400">No users matched your filter criteria.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection

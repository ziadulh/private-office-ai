@extends('layouts.app')

@section('title', 'Departments — Private Office AI')
@section('header', 'Department Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <p class="text-sm text-slate-500">Manage organizational units, department heads, and operational status.</p>
    @if(auth()->user()->hasPermission('departments.manage'))
        <a href="{{ route('departments.create') }}"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm">
            + New Department
        </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
        <tr>
            <th class="px-6 py-3.5">Code</th>
            <th class="px-6 py-3.5">Department Name</th>
            <th class="px-6 py-3.5">Department Manager</th>
            <th class="px-6 py-3.5">Employees</th>
            <th class="px-6 py-3.5">Documents</th>
            <th class="px-6 py-3.5">Status</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($departments as $dept)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-mono text-xs font-semibold text-indigo-600">{{ $dept->code }}</td>
                <td class="px-6 py-4">
                    <div class="font-medium text-slate-900">{{ $dept->name }}</div>
                    <div class="text-xs text-slate-400">{{ $dept->description }}</div>
                </td>
                <td class="px-6 py-4 text-slate-600">{{ $dept->manager?->name ?? 'Not Assigned' }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $dept->users_count }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $dept->documents_count }}</td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-0.5 text-xs rounded-full {{ $dept->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $dept->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                    @if(auth()->user()->hasPermission('departments.manage'))
                        <a href="{{ route('departments.edit', $dept) }}" class="text-indigo-600 hover:underline font-medium">Edit</a>
                        <form action="{{ route('departments.toggle', $dept) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-slate-500 hover:text-slate-800">
                                {{ $dept->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

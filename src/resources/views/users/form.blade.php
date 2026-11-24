@extends('layouts.app')

@section('title', ($user->exists ? 'Edit' : 'Add') . ' Employee — Private Office AI')
@section('header', $user->exists ? 'Edit Employee: ' . $user->name : 'Register New Employee')

@section('content')
<div class="max-w-3xl bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="space-y-5">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Employee ID</label>
                <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" placeholder="e.g. EMP-0002"
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Work Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                <select name="department_id" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                    <option value="">-- Unassigned --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id', $user->department_id) == $dept->id)>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Designation</label>
                <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" placeholder="e.g. Senior Analyst"
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Password {{ $user->exists ? '(Leave blank to keep current)' : '*' }}
                </label>
                <input type="password" name="password" {{ $user->exists ? '' : 'required' }}
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }}
                       class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Assign Roles *</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($roles as $role)
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm cursor-pointer">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               @checked(in_array($role->id, old('roles', $assignedRoles)))
                               class="rounded border-slate-300 text-indigo-600">
                        <span>{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-slate-300 text-indigo-600">
                <span>Account is active (Employee can sign in)</span>
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                {{ $user->exists ? 'Update Employee' : 'Create Employee' }}
            </button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
</div>
@endsection

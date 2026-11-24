@extends('layouts.app')

@section('title', 'Admin Dashboard — Private Office AI')
@section('header', 'Executive Overview')

@section('content')
<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Users</div>
        <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total_users'] }}</div>
        <div class="mt-1 text-xs text-slate-500">Registered employee accounts</div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Users</div>
        <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['active_users'] }}</div>
        <div class="mt-1 text-xs text-slate-500">Authorized to sign in</div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Departments</div>
        <div class="mt-2 text-3xl font-bold text-indigo-600">{{ $stats['total_departments'] }}</div>
        <div class="mt-1 text-xs text-slate-500">Organizational units</div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Documents</div>
        <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total_documents'] }}</div>
        <div class="mt-1 text-xs text-slate-500">Stored in private vault</div>
    </div>
</div>

<!-- System Status & Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- System Status -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">System Health & Status</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="text-slate-500">Primary Database</span>
                <span class="font-medium text-emerald-600">{{ $systemStatus['database'] }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="text-slate-500">Document Vault</span>
                <span class="font-medium text-emerald-600">{{ $systemStatus['storage'] }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-slate-500">Local AI Runtime</span>
                <span class="font-medium text-indigo-600">{{ $systemStatus['ai_engine'] }}</span>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm lg:col-span-2">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Recently Created Users</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="border-b border-slate-200 text-xs uppercase text-slate-400">
                    <th class="pb-2">Employee</th>
                    <th class="pb-2">Department</th>
                    <th class="pb-2">Role</th>
                    <th class="pb-2">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($recentUsers as $u)
                    <tr>
                        <td class="py-2.5">
                            <div class="font-medium text-slate-800">{{ $u->name }}</div>
                            <div class="text-xs text-slate-400">{{ $u->email }}</div>
                        </td>
                        <td class="py-2.5 text-slate-600">{{ $u->department?->name ?? 'Unassigned' }}</td>
                        <td class="py-2.5 text-slate-600">{{ $u->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="py-2.5">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $u->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $u->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Audit Logs -->
<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-800 mb-4">Recent Administrative & Security Activities</h2>
    @if($recentAudits->isEmpty())
        <p class="text-sm text-slate-400">No audit events recorded yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="border-b border-slate-200 text-xs uppercase text-slate-400">
                    <th class="pb-2">Timestamp</th>
                    <th class="pb-2">User</th>
                    <th class="pb-2">Action</th>
                    <th class="pb-2">IP Address</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($recentAudits as $log)
                    <tr>
                        <td class="py-2.5 text-slate-500 text-xs">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="py-2.5 font-medium text-slate-800">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="py-2.5">
                            <span class="px-2 py-0.5 text-xs font-mono bg-slate-100 text-slate-700 rounded">{{ $log->action }}</span>
                        </td>
                        <td class="py-2.5 text-slate-500 text-xs">{{ $log->ip_address }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

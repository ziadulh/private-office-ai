<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Private Office AI')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-200 flex flex-col shrink-0">
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <span class="text-lg font-bold tracking-wide text-white">Private Office <span class="text-indigo-400">AI</span></span>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 text-sm">
            <a href="{{ route('dashboard') }}"
               class="flex items-center px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Dashboard
            </a>

            <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Administration</div>

            @if(auth()->user()->hasPermission('users.view'))
                <a href="{{ route('users.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Users
                </a>
            @endif

            @if(auth()->user()->hasPermission('roles.manage'))
                <a href="{{ route('roles.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('roles.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Roles & Permissions
                </a>
            @endif

            @if(auth()->user()->hasPermission('departments.view'))
                <a href="{{ route('departments.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('departments.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Departments
                </a>
            @endif

            <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Knowledge Base</div>
            <a href="{{ route('documents.index') }}" class="flex items-center px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('documents.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Documents</a>
            <a href="{{ route('chat.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('chat.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><span>AI Assistant</span><span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded">Live</span></a>
        </nav>

        <div class="p-4 border-t border-slate-800 text-xs text-slate-400">
            <div class="font-medium text-slate-200">{{ auth()->user()->name }}</div>
            <div class="truncate">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'Employee' }}</div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-800">@yield('header', 'Dashboard')</h1>

            <div class="flex items-center gap-4">
                <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">
                    Internal Secure Mode
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-slate-600 hover:text-rose-600 transition">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>

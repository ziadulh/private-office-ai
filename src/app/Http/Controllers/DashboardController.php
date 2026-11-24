<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_departments' => Department::where('is_active', true)->count(),
            'total_documents' => Document::count(),
        ];

        $recentDocuments = Document::with(['department', 'category', 'uploader'])
            ->latest()
            ->take(5)
            ->get();

        $recentUsers = User::with(['department', 'roles'])
            ->latest()
            ->take(5)
            ->get();

        $recentAudits = AuditLog::with('user')
            ->latest('created_at')
            ->take(8)
            ->get();

        $systemStatus = [
            'database' => DB::connection()->getPdo() ? 'Connected (MySQL 8)' : 'Disconnected',
            'storage' => is_writable(storage_path('app')) ? 'Writable (Private Disk)' : 'Read-Only',
            'ai_engine' => 'Standby (Phase 3 - Ollama Ready)',
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentDocuments',
            'recentUsers',
            'recentAudits',
            'systemStatus'
        ));
    }
}

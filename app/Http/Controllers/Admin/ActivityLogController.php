<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'action']);

        $logs = ActivityLog::with('user')
            ->when($filters['user_id'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.activity-logs.index', compact('logs', 'users', 'actions', 'filters'));
    }
}

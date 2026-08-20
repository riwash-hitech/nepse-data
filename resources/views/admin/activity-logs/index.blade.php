@extends('layouts.app')
@section('title', 'Activity Logs')

@php
    $actionLabels = [
        'login' => 'Login',
        'logout' => 'Logout',
        'login_failed' => 'Failed Login',
        'register' => 'Registration',
        'profile_update' => 'Profile Update',
        'password_change' => 'Password Change',
        'watchlist_add' => 'Watchlist Add',
        'watchlist_remove' => 'Watchlist Remove',
        'portfolio_buy' => 'Portfolio Buy',
        'portfolio_sell' => 'Portfolio Sell',
        'admin_user_create' => 'Admin: Create User',
        'admin_user_update' => 'Admin: Update User',
        'admin_user_delete' => 'Admin: Delete User',
        'admin_user_block' => 'Admin: Block User',
        'admin_user_unblock' => 'Admin: Unblock User',
        'admin_force_logout' => 'Admin: Force Logout',
        'admin_sync' => 'Admin: Sync',
        'admin_scrape_now' => 'Admin: Scrape Now',
        'admin_view_portfolio' => 'Admin: View Portfolio',
        'admin_view_watchlist' => 'Admin: View Watchlist',
    ];
    $badgeColors = [
        'login' => ['#DCFCE7', '#14532D'],
        'logout' => ['#f1f5f9', '#475569'],
        'login_failed' => ['#fef2f2', '#dc2626'],
        'register' => ['#eff6ff', '#1d4ed8'],
    ];
@endphp

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">🕒 User Activity Logs</h1>
    </div>

    <div class="glass p-4">
        <form method="GET" action="{{ route('admin.activity-logs') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">User</label>
                <select name="user_id" class="px-3 py-2 text-sm rounded-lg" style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;min-width:180px;">
                    <option value="">All users</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Action</label>
                <select name="action" class="px-3 py-2 text-sm rounded-lg" style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;min-width:180px;">
                    <option value="">All actions</option>
                    @foreach($actions as $a)
                    <option value="{{ $a }}" {{ ($filters['action'] ?? '') === $a ? 'selected' : '' }}>{{ $actionLabels[$a] ?? $a }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            @if(($filters['user_id'] ?? null) || ($filters['action'] ?? null))
            <a href="{{ route('admin.activity-logs') }}" class="btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <div class="glass overflow-hidden">
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">When</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">User</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Action</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Description</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap" style="color:#64748b;">
                        {{ $log->created_at?->format('d M Y, h:i A') }}
                    </td>
                    <td class="px-4 py-3" style="color:#0f172a;">
                        @if($log->user)
                            <div class="font-semibold">{{ $log->user->name }}</div>
                            <div class="text-xs" style="color:#94a3b8;">{{ $log->user->email }}</div>
                        @else
                            <span style="color:#cbd5e1;">Guest</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php [$bg, $fg] = $badgeColors[$log->action] ?? ['#f1f5f9', '#475569']; @endphp
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:{{ $bg }};color:{{ $fg }};">
                            {{ $actionLabels[$log->action] ?? $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3" style="color:#475569;">{{ $log->description }}</td>
                    <td class="px-4 py-3 whitespace-nowrap" style="color:#94a3b8;">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center" style="color:#94a3b8;">No activity recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div>{{ $logs->links() }}</div>
</div>
@endsection

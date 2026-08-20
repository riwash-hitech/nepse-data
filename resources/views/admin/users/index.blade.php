@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">👤 User Management</h1>
        <a href="{{ route('admin.users.create') }}" class="btn-primary">+ Create User</a>
    </div>

    <div class="glass overflow-hidden">
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Name</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Email</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Phone</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Role</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Joined</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-semibold" style="color:#0f172a;">
                        {{ $u->name }}
                        @if($u->id === auth()->id())
                        <span class="text-xs" style="color:#94a3b8;">(you)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3" style="color:#475569;">{{ $u->email }}</td>
                    <td class="px-4 py-3" style="color:#475569;">{{ $u->phone ?: '—' }}</td>
                    <td class="text-center px-4 py-3">
                        @if($u->isAdmin())
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:#DCFCE7;color:#14532D;border:1px solid #bbf7d0;">Admin</span>
                        @else
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">User</span>
                        @endif
                        @if($u->isBlocked())
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">Blocked</span>
                        @endif
                    </td>
                    <td class="px-4 py-3" style="color:#64748b;">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="text-center px-4 py-3">
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                            <a href="{{ route('admin.users.edit', $u) }}"
                               class="text-xs px-3 py-1 rounded-md transition-colors"
                               style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                Edit
                            </a>
                            <a href="{{ route('admin.users.portfolio', $u) }}"
                               class="text-xs px-3 py-1 rounded-md transition-colors"
                               style="background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;">
                                Portfolio
                            </a>
                            <a href="{{ route('admin.users.watchlist', $u) }}"
                               class="text-xs px-3 py-1 rounded-md transition-colors"
                               style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;">
                                Watchlist
                            </a>
                        @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.force-logout', $u) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        onclick="return confirm('Log {{ $u->name }} out of all devices?')"
                                        class="text-xs px-3 py-1 rounded-md transition-colors"
                                        style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;">
                                    Logout
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.toggle-block', $u) }}" class="inline">
                                @csrf
                                @if($u->isBlocked())
                                <button type="submit"
                                        class="text-xs px-3 py-1 rounded-md transition-colors"
                                        style="background:#DCFCE7;color:#14532D;border:1px solid #bbf7d0;">
                                    Unblock
                                </button>
                                @else
                                <button type="submit"
                                        onclick="return confirm('Block {{ $u->name }}? They will be logged out immediately.')"
                                        class="text-xs px-3 py-1 rounded-md transition-colors"
                                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                                    Block
                                </button>
                                @endif
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Delete {{ $u->name }}?')"
                                        class="text-xs px-3 py-1 rounded-md transition-colors"
                                        style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
                                    Delete
                                </button>
                            </form>
                        @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div>{{ $users->links() }}</div>
</div>
@endsection

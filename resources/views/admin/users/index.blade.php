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
                    <td class="text-center px-4 py-3">
                        @if($u->isAdmin())
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;">Admin</span>
                        @else
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">User</span>
                        @endif
                    </td>
                    <td class="px-4 py-3" style="color:#64748b;">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="text-center px-4 py-3">
                        @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Delete {{ $u->name }}?')"
                                    class="text-xs px-3 py-1 rounded-md transition-colors"
                                    style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                                Delete
                            </button>
                        </form>
                        @else
                        <span style="color:#cbd5e1;">—</span>
                        @endif
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

@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">✏️ Edit User</h1>
        <a href="{{ route('admin.users.index') }}" class="btn-ghost">← Back to Users</a>
    </div>

    @if($errors->any())
    <div class="px-4 py-3 rounded-lg text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="glass p-5">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4 max-w-lg">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Name *</label>
                <input type="text" name="name" required value="{{ old('name', $user->name) }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Email *</label>
                <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Confirm New Password</label>
                    <input type="password" name="password_confirmation" placeholder="Leave blank to keep current"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Role *</label>
                <select name="role_id" required {{ $user->id === auth()->id() ? 'disabled' : '' }}
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                    <option value="{{ \App\Models\User::ROLE_USER }}" {{ old('role_id', $user->role_id) == \App\Models\User::ROLE_USER ? 'selected' : '' }}>User</option>
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role_id', $user->role_id) == \App\Models\User::ROLE_ADMIN ? 'selected' : '' }}>Admin</option>
                </select>
                @if($user->id === auth()->id())
                <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                <p class="text-xs mt-1" style="color:#94a3b8;">You cannot change your own role.</p>
                @endif
            </div>
            <button type="submit" class="btn-primary" style="margin-top:0.5rem;">Save Changes</button>
        </form>
    </div>
</div>
@endsection

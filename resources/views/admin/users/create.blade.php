@extends('layouts.app')
@section('title', 'Create User')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">➕ Create User</h1>
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
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 max-w-lg">
            @csrf
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Name *</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Email *</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Password *</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#334155;">Role *</label>
                <select name="role_id" required
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                    <option value="{{ \App\Models\User::ROLE_USER }}" {{ old('role_id', \App\Models\User::ROLE_USER) == \App\Models\User::ROLE_USER ? 'selected' : '' }}>User</option>
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role_id') == \App\Models\User::ROLE_ADMIN ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:0.5rem;">Create User</button>
        </form>
    </div>
</div>
@endsection

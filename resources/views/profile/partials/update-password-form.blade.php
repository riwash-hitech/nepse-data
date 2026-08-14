<div>
    <h2 class="text-base font-semibold" style="color:#0f172a;">Update Password</h2>
    <p class="text-sm mt-1" style="color:#64748b;">Ensure your account is using a long, random password to stay secure.</p>
</div>

<form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4">
    @csrf
    @method('put')

    <div>
        <label class="block text-xs font-semibold mb-1" style="color:#334155;">Current Password</label>
        <input type="password" name="current_password" autocomplete="current-password"
               class="w-full px-3 py-2 text-sm rounded-lg"
               style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
        @error('current_password', 'updatePassword')
        <div class="text-xs mt-1" style="color:#dc2626;">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1" style="color:#334155;">New Password</label>
        <input type="password" name="password" autocomplete="new-password"
               class="w-full px-3 py-2 text-sm rounded-lg"
               style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
        @error('password', 'updatePassword')
        <div class="text-xs mt-1" style="color:#dc2626;">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1" style="color:#334155;">Confirm Password</label>
        <input type="password" name="password_confirmation" autocomplete="new-password"
               class="w-full px-3 py-2 text-sm rounded-lg"
               style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
        @error('password_confirmation', 'updatePassword')
        <div class="text-xs mt-1" style="color:#dc2626;">{{ $message }}</div>
        @enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">Save</button>
        @if (session('status') === 'password-updated')
        <span class="text-sm" style="color:#16a34a;">Saved.</span>
        @endif
    </div>
</form>

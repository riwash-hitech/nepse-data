<div>
    <h2 class="text-base font-semibold" style="color:#0f172a;">Profile Information</h2>
    <p class="text-sm mt-1" style="color:#64748b;">Update your account's name and email address.</p>
</div>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
    @csrf
    @method('patch')

    <div>
        <label class="block text-xs font-semibold mb-1" style="color:#334155;">Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
               class="w-full px-3 py-2 text-sm rounded-lg"
               style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
        @error('name')
        <div class="text-xs mt-1" style="color:#dc2626;">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1" style="color:#334155;">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
               class="w-full px-3 py-2 text-sm rounded-lg"
               style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
        @error('email')
        <div class="text-xs mt-1" style="color:#dc2626;">{{ $message }}</div>
        @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div class="text-xs mt-2" style="color:#64748b;">
            Your email address is unverified.
            <button form="send-verification" class="underline" style="color:#2563eb;">Click here to re-send the verification email.</button>
            @if (session('status') === 'verification-link-sent')
            <div class="mt-1 font-medium" style="color:#16a34a;">A new verification link has been sent to your email address.</div>
            @endif
        </div>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">Save</button>
        @if (session('status') === 'profile-updated')
        <span class="text-sm" style="color:#16a34a;">Saved.</span>
        @endif
    </div>
</form>

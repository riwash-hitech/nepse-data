<div>
    <h2 class="text-base font-semibold" style="color:#0f172a;">Delete Account</h2>
    <p class="text-sm mt-1" style="color:#64748b;">
        Once your account is deleted, all of its data will be permanently deleted. This cannot be undone.
    </p>
</div>

<button type="button" id="showDeleteFormBtn" onclick="document.getElementById('deleteAccountForm').style.display='block';this.style.display='none';"
        class="mt-4 text-xs px-3 py-1.5 rounded-md transition-colors"
        style="display:{{ $errors->userDeletion->isNotEmpty() ? 'none' : 'inline-flex' }};background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
    Delete Account
</button>

<form method="post" action="{{ route('profile.destroy') }}" id="deleteAccountForm"
      style="display:{{ $errors->userDeletion->isNotEmpty() ? 'block' : 'none' }};margin-top:1rem;">
    @csrf
    @method('delete')

    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Confirm your password to delete your account</label>
    <input type="password" name="password" placeholder="Password"
           class="w-full px-3 py-2 text-sm rounded-lg"
           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;max-width:20rem;">
    @error('password', 'userDeletion')
    <div class="text-xs mt-1" style="color:#dc2626;">{{ $message }}</div>
    @enderror

    <div class="flex items-center gap-3 mt-3">
        <button type="submit"
                onclick="return confirm('This will permanently delete your account. Are you sure?')"
                class="text-xs px-3 py-1.5 rounded-md transition-colors"
                style="background:#dc2626;color:#fff;border:none;">
            Permanently Delete Account
        </button>
        <button type="button" onclick="document.getElementById('deleteAccountForm').style.display='none';document.getElementById('showDeleteFormBtn').style.display='inline-flex';"
                class="btn-ghost">
            Cancel
        </button>
    </div>
</form>

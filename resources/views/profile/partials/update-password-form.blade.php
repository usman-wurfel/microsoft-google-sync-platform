<div class="mb-4">
    <h5 class="card-title mb-3">
        <i class="fas fa-shield-alt me-2 text-primary"></i>
        Update Password
    </h5>
    <p class="text-muted mb-4">
        Ensure your account is using a long, random password to stay secure.
    </p>
</div>

<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="mb-3">
        <label for="update_password_current_password" class="form-label">
            <i class="fas fa-key me-1"></i>Current Password
        </label>
        <input type="password" 
               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
               id="update_password_current_password" 
               name="current_password" 
               autocomplete="current-password">
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="update_password_password" class="form-label">
            <i class="fas fa-lock me-1"></i>New Password
        </label>
        <input type="password" 
               class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
               id="update_password_password" 
               name="password" 
               autocomplete="new-password">
        @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="update_password_password_confirmation" class="form-label">
            <i class="fas fa-lock me-1"></i>Confirm Password
        </label>
        <input type="password" 
               class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
               id="update_password_password_confirmation" 
               name="password_confirmation" 
               autocomplete="new-password">
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-check-circle me-2"></i>Save
        </button>

        @if (session('status') === 'password-updated')
            <div class="alert alert-success mb-0 py-2" 
                 x-data="{ show: true }"
                 x-show="show"
                 x-transition
                 x-init="setTimeout(() => show = false, 2000)">
                <i class="fas fa-check-circle me-2"></i>Saved.
            </div>
        @endif
    </div>
</form>

<div class="mb-4">
    <h5 class="card-title mb-3">
        <i class="fas fa-user-cog me-2 text-primary"></i>
        Profile Information
    </h5>
    <p class="text-muted mb-4">
        Update your account's profile information. Email address cannot be changed.
    </p>
</div>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="name" class="form-label">
            <i class="fas fa-user me-1"></i>Name
        </label>
        <input type="text" 
               class="form-control @error('name') is-invalid @enderror" 
               id="name" 
               name="name" 
               value="{{ old('name', $user->name) }}" 
               required 
               autofocus 
               autocomplete="name">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">
            <i class="fas fa-envelope me-1"></i>Email
        </label>
        <input type="email" 
               class="form-control" 
               id="email" 
               value="{{ $user->email }}" 
               readonly
               style="background-color: #f8f9fa;">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>Email address cannot be changed
        </small>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Your email address is unverified.
                    <button form="send-verification" class="btn btn-link p-0 ms-2">
                        Click here to re-send the verification email.
                    </button>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        A new verification link has been sent to your email address.
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-check-circle me-2"></i>Save
        </button>

        @if (session('status') === 'profile-updated')
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

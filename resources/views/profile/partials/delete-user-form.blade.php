@if(!(Auth::user()->hasRole('admin') && Auth::user()->email === 'admin@domain.com'))
    <div class="mb-4">
        <h5 class="card-title mb-3">
            <i class="fas fa-trash me-2 text-danger"></i>
            Delete Account
        </h5>
        <p class="text-muted mb-4">
            Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
        </p>
    </div>

    <button type="button" 
            class="btn btn-danger"
            data-bs-toggle="modal" 
            data-bs-target="#confirmUserDeletion">
        <i class="fas fa-trash me-2"></i>Delete Account
    </button>
@else
    <div class="mb-4">
        <h5 class="card-title mb-3">
            <i class="fas fa-shield-alt me-2 text-warning"></i>
            Account Protection
        </h5>
        <div class="alert alert-warning">
            <i class="fas fa-shield-alt me-2"></i>
            <strong>Protected Account:</strong> This admin account cannot be deleted for security reasons.
        </div>
        <p class="text-muted mb-4">
            As the primary administrator, this account is protected from deletion to ensure system security and continuity.
        </p>
    </div>
@endif

@if(!Auth::user()->hasRole('admin'))
<!-- Modal -->
<div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmUserDeletionLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Delete Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to delete your account?
                    </div>
                    
                    <p class="text-muted">
                        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                    </p>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-key me-1"></i>Password
                        </label>
                        <input type="password" 
                               class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

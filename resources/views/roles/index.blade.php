<x-app-layout>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-4">
                    <h1 class="display-6 fw-bold text-dark mb-2">
                        <i class="fas fa-users text-primary me-3"></i>
                        Role Management
                    </h1>
                    <p class="lead text-muted mb-0">Manage user roles and permissions</p>
                </div>
            </div>
        </div>
    </div>


    <!-- Create New User Form (Hidden by default) -->
    <div class="row mb-4" id="createUserFormContainer" style="display: none;">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2 text-primary"></i>
                        Create New User
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.store') }}" id="createUserForm" onsubmit="return validateCreateUserForm(event)">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Full Name
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Password
                                </label>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Confirm Password
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create User
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" onclick="toggleCreateForm()">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <small class="text-muted ms-3">
                                    <i class="fas fa-info-circle me-1"></i>New users will be assigned "User" role by default
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Users and Roles -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2 text-primary"></i>
                        Users and Their Roles
                    </h5>
                    <button type="button" class="btn btn-primary" onclick="toggleCreateForm()">
                        <i class="fas fa-user-plus me-2"></i>Create User
                    </button>
                </div>
                <div class="card-body">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user me-1"></i>Name</th>
                                        <th><i class="fas fa-envelope me-1"></i>Email</th>
                                        <th><i class="fas fa-shield-alt me-1"></i>Roles</th>
                                        <th><i class="fas fa-cog me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                                    <strong>{{ $user->name }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-envelope me-1 text-muted"></i>
                                                {{ $user->email }}
                                            </td>
                                            <td>
                                                @if($user->roles->count() > 0)
                                                    @foreach($user->roles as $role)
                                                        <span class="badge bg-primary me-1">
                                                            <i class="fas fa-shield-alt me-1"></i>
                                                            {{ $role->name }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No roles assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @if($user->hasRole('admin') && $user->email === 'admin@domain.com')
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-shield-alt me-1"></i>Protected
                                                        </span>
                                                    @else
                                                        @role('admin')
                                                        <form method="POST" action="{{ route('users.assign-role', $user) }}" class="d-inline">
                                                            @csrf
                                                            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                                <option value="">Assign Role</option>
                                                                @foreach($roles as $role)
                                                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="confirmDelete(event)">
                                                                <i class="fas fa-trash me-1"></i>Delete
                                                            </button>
                                                        </form>
                                                        @endrole
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users display-1 text-muted"></i>
                            <p class="text-muted mt-3">No users found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Roles and Permissions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2 text-primary"></i>
                        Roles and Permissions
                    </h5>
                </div>
                <div class="card-body">
                    @if($roles->count() > 0)
                        <div class="row g-3">
                            @foreach($roles as $role)
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="bi bi-shield-check text-primary me-2"></i>
                                                {{ ucfirst($role->name) }}
                                            </h6>
                                            <div class="mt-3">
                                                @if($role->permissions->count() > 0)
                                                    @foreach($role->permissions as $permission)
                                                        <span class="badge bg-success me-1 mb-1">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            {{ $permission->name }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No permissions assigned</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-shield display-1 text-muted"></i>
                            <p class="text-muted mt-3">No roles found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function confirmDelete(event) {
    event.preventDefault();
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form
            event.target.closest('form').submit();
        }
    });
}

function toggleCreateForm() {
    const form = document.getElementById('createUserFormContainer');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = 'none';
    }
}

function validateCreateUserForm(event) {
    event.preventDefault();
    
    // Get form elements
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const passwordConfirmation = document.getElementById('password_confirmation');
    
    // Clear previous error states
    clearValidationErrors();
    
    let isValid = true;
    let errorMessages = [];
    
    // Validate Name
    if (!name.value.trim()) {
        showFieldError(name, 'Name is required');
        isValid = false;
        errorMessages.push('Name is required');
    } else if (name.value.trim().length < 2) {
        showFieldError(name, 'Name must be at least 2 characters');
        isValid = false;
        errorMessages.push('Name must be at least 2 characters');
    } else if (name.value.trim().length > 255) {
        showFieldError(name, 'Name must not exceed 255 characters');
        isValid = false;
        errorMessages.push('Name must not exceed 255 characters');
    }
    
    // Validate Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email.value.trim()) {
        showFieldError(email, 'Email is required');
        isValid = false;
        errorMessages.push('Email is required');
    } else if (!emailRegex.test(email.value.trim())) {
        showFieldError(email, 'Please enter a valid email address');
        isValid = false;
        errorMessages.push('Please enter a valid email address');
    } else if (email.value.trim().length > 255) {
        showFieldError(email, 'Email must not exceed 255 characters');
        isValid = false;
        errorMessages.push('Email must not exceed 255 characters');
    }
    
    // Validate Password
    if (!password.value) {
        showFieldError(password, 'Password is required');
        isValid = false;
        errorMessages.push('Password is required');
    } else if (password.value.length < 8) {
        showFieldError(password, 'Password must be at least 8 characters');
        isValid = false;
        errorMessages.push('Password must be at least 8 characters');
    }
    
    // Validate Password Confirmation
    if (!passwordConfirmation.value) {
        showFieldError(passwordConfirmation, 'Password confirmation is required');
        isValid = false;
        errorMessages.push('Password confirmation is required');
    } else if (password.value !== passwordConfirmation.value) {
        showFieldError(passwordConfirmation, 'Passwords do not match');
        isValid = false;
        errorMessages.push('Passwords do not match');
    }
    
    if (!isValid) {
        // Show error toastr
        toastr.error('Please fix the following errors:<br>' + errorMessages.join('<br>'));
        return false;
    }
    
    // If validation passes, submit the form
    event.target.submit();
    return true;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearValidationErrors() {
    const form = document.getElementById('createUserForm');
    const fields = form.querySelectorAll('.form-control');
    
    fields.forEach(field => {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    });
}

// Add real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createUserForm');
    if (form) {
        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordConfirmation = document.getElementById('password_confirmation');
        
        // Real-time validation for name
        if (name) {
            name.addEventListener('blur', function() {
                if (this.value.trim() && this.value.trim().length < 2) {
                    showFieldError(this, 'Name must be at least 2 characters');
                } else if (this.value.trim() && this.value.trim().length > 255) {
                    showFieldError(this, 'Name must not exceed 255 characters');
                } else if (this.value.trim()) {
                    clearFieldError(this);
                }
            });
        }
        
        // Real-time validation for email
        if (email) {
            email.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value.trim() && !emailRegex.test(this.value.trim())) {
                    showFieldError(this, 'Please enter a valid email address');
                } else if (this.value.trim() && this.value.trim().length > 255) {
                    showFieldError(this, 'Email must not exceed 255 characters');
                } else if (this.value.trim()) {
                    clearFieldError(this);
                }
            });
        }
        
        // Real-time validation for password
        if (password) {
            password.addEventListener('blur', function() {
                if (this.value && this.value.length < 8) {
                    showFieldError(this, 'Password must be at least 8 characters');
                } else if (this.value) {
                    clearFieldError(this);
                }
            });
        }
        
        // Real-time validation for password confirmation
        if (passwordConfirmation) {
            passwordConfirmation.addEventListener('blur', function() {
                if (this.value && password.value && this.value !== password.value) {
                    showFieldError(this, 'Passwords do not match');
                } else if (this.value && password.value && this.value === password.value) {
                    clearFieldError(this);
                }
            });
        }
    }
});

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

</script>

<x-app-layout>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-4">
                    <h1 class="display-6 fw-bold text-dark mb-2">
                        <i class="fas fa-cog text-primary me-3"></i>
                        Platform Settings
                    </h1>
                    <p class="lead text-muted mb-0">Manage your platform branding and configuration</p>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Platform Name and Version Row -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="platform_name" class="form-label">
                                    <i class="fas fa-building me-1"></i>Platform Name
                                </label>
                                <input type="text" 
                                       class="form-control @error('platform_name') is-invalid @enderror" 
                                       id="platform_name" 
                                       name="platform_name" 
                                       value="{{ old('platform_name', $settings->platform_name) }}" 
                                       required>
                                @error('platform_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="version" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Version
                                </label>
                                <input type="text" 
                                       class="form-control @error('version') is-invalid @enderror" 
                                       id="version" 
                                       name="version" 
                                       value="{{ old('version', $settings->version) }}" 
                                       required>
                                @error('version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description Row -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="3">{{ old('description', $settings->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Logo and Favicon Row -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="logo" class="form-label">
                                    <i class="fas fa-image me-1"></i>Logo
                                </label>
                                <input type="file" 
                                       class="form-control @error('logo') is-invalid @enderror" 
                                       id="logo" 
                                       name="logo" 
                                       accept="image/*">
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                @if($settings->logo_path)
                                    <div class="mt-2">
                                        <small class="text-muted">Current logo:</small>
                                        <div class="mt-1">
                                            <img src="{{ asset($settings->logo_path) }}" 
                                                 alt="Current Logo" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 100px; max-height: 100px;">
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="favicon" class="form-label">
                                    <i class="fas fa-star me-1"></i>Favicon
                                </label>
                                <input type="file" 
                                       class="form-control @error('favicon') is-invalid @enderror" 
                                       id="favicon" 
                                       name="favicon" 
                                       accept="image/*">
                                @error('favicon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                @if($settings->favicon_path)
                                    <div class="mt-2">
                                        <small class="text-muted">Current favicon:</small>
                                        <div class="mt-1">
                                            <img src="{{ asset($settings->favicon_path) }}" 
                                                 alt="Current Favicon" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 32px; max-height: 32px;">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check-circle me-2"></i>Update Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

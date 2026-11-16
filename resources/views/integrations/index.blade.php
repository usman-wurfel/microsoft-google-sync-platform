<x-app-layout>
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-5">
                    <h1 class="display-6 fw-bold text-dark mb-3">
                        <i class="fas fa-puzzle-piece text-primary me-3"></i>
                        Company Integrations
                    </h1>
                    <p class="lead text-muted mb-0">
                        Connect and manage external platform integrations
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Integrations Grid -->
    
    <!-- Flash Messages -->
    @if(session('error'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Connection Failed!</strong>
                        <p class="mb-0 mt-1">{{ session('error') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif
    
    @if(session('success'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Success!</strong>
                        <p class="mb-0 mt-1">{{ session('success') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif
    
    <div class="row g-4">
        @foreach($integrations as $integration)
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-lg h-100 integration-card {{ $integration['status'] === 'beta' ? 'beta-card' : '' }}">
                @if($integration['status'] === 'beta')
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="badge bg-warning text-dark">BETA</span>
                </div>
                @endif
                
                <div class="card-body d-flex flex-column">
                    <!-- Icon -->
                    <div class="text-center mb-4">
                        <div class="integration-icon-wrapper mb-3">
                            <i class="{{ $integration['icon'] }} text-{{ $integration['color'] }}" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="card-title fw-bold">{{ $integration['name'] }}</h5>
                    </div>
                    
                    <!-- Description -->
                    <p class="card-text text-muted small flex-grow-1">
                        {{ $integration['description'] }}
                    </p>
                    
                    <!-- Connect/Disconnect & Test Buttons -->
                    <div class="mt-3">
                        @if($integration['is_connected'])
                            <!-- Connected State -->
                            <button class="btn btn-success w-100 mb-2" disabled>
                                <i class="fas fa-check-circle me-2"></i>
                                Connected
                            </button>
                            
                            <!-- Disconnect (Optional - logic to be implemented) -->
                            <a href="{{ route('integrations.disconnect', $integration['id']) }}" class="btn btn-outline-danger w-100 mb-2 btn-sm">
                                Disconnect
                            </a>

                            <!-- Test Button (Only if connected) -->
                            @if(isset($integration['test_route']))
                            <a href="{{ route($integration['test_route']) }}" 
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-vial me-2"></i>
                                Test Integration
                            </a>
                            @endif
                        @else
                            <!-- Disconnected State -->
                            <a href="{{ route($integration['route']) }}" 
                               class="btn btn-{{ $integration['color'] }} w-100 mb-2">
                                <i class="fas fa-plug me-2"></i>
                                {{ ucfirst($integration['connect_label']) }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Information Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle me-3 mt-1 text-info" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="alert-heading mb-2">Integration Requirements</h6>
                        <p class="mb-2">
                            <strong>Microsoft Integrations:</strong> Require Microsoft Work/School Account (Azure AD/Entra ID) with appropriate admin permissions.
                        </p>
                        <p class="mb-2">
                            <strong>Google Integrations:</strong> Require Google Workspace Admin Account with Directory API access.
                        </p>
                        <p class="mb-0">
                            <strong>Note:</strong> Some integrations require admin consent before they can be used. Make sure you have the necessary permissions in your organization.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<style>
.integration-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.integration-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important;
}

.integration-icon-wrapper {
    width: 100px;
    height: 100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(13,110,253,.1) 0%, rgba(13,110,253,.05) 100%);
}

.beta-card {
    border: 2px dashed #ffc107 !important;
}

.beta-card .integration-icon-wrapper {
    background: linear-gradient(135deg, rgba(255,193,7,.1) 0%, rgba(255,193,7,.05) 100%);
}

.card-title {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.card-text {
    font-size: 0.9rem;
    line-height: 1.6;
}

.btn {
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: scale(1.05);
}
</style>

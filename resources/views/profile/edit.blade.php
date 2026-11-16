<x-app-layout>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-4">
                    <h1 class="display-6 fw-bold text-dark mb-2">
                        <i class="fas fa-user-circle text-primary me-3"></i>
                        Profile Settings
                    </h1>
                    <p class="lead text-muted mb-0">Manage your account information and security</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Profile Information -->
            <div class="card border-0 shadow-lg mb-4">
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Update Password -->
            <div class="card border-0 shadow-lg mb-4">
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Delete Account -->
            <div class="card border-0 shadow-lg">
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

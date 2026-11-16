<x-app-layout>
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('sync.dashboard') }}" class="text-decoration-none text-light">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-user me-1"></i>User Details
            </li>
        </ol>
    </nav>

    <!-- User Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                @if($user->profile_photo_url)
                                    <img src="{{ asset($user->profile_photo_url) }}" alt="Profile Photo" 
                                         class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 80px; height: 80px;">
                                        <i class="fas fa-user text-white" style="font-size: 2rem;"></i>
                                    </div>
                                @endif
                                <div>
                                    <h1 class="h3 mb-1">{{ $user->display_name }}</h1>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-envelope me-1"></i>{{ $user->email }}
                                    </p>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-{{ $user->platform === 'microsoft' ? 'primary' : 'danger' }} fs-6">
                                            <i class="fab fa-{{ $user->platform === 'microsoft' ? 'microsoft' : 'google' }} me-1"></i>
                                            {{ ucfirst($user->platform) }}
                                        </span>
                                        <span class="badge bg-{{ $user->account_enabled ? 'success' : 'danger' }} fs-6">
                                            <i class="fas fa-{{ $user->account_enabled ? 'check-circle' : 'x-circle' }} me-1"></i>
                                            {{ $user->account_enabled ? 'Active' : 'Disabled' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="text-muted small">
                                <div><i class="fas fa-calendar me-1"></i>Synced: {{ $user->created_at->format('M j, Y g:i A') }}</div>
                                @if($user->last_sign_in)
                                    <div><i class="fas fa-clock me-1"></i>Last Sign-in: {{ $user->last_sign_in->format('M j, Y g:i A') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent border-0">
                    <ul class="nav nav-tabs card-header-tabs" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                <i class="fas fa-user me-1"></i>Basic Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">
                                <i class="fas fa-phone me-1"></i>Contact
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="work-tab" data-bs-toggle="tab" data-bs-target="#work" type="button" role="tab">
                                <i class="fas fa-briefcase me-1"></i>Work
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address" type="button" role="tab">
                                <i class="fas fa-map-marker-alt me-1"></i>Address
                            </button>
                        </li>
                        @if($user->platform === 'microsoft')
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="microsoft-tab" data-bs-toggle="tab" data-bs-target="#microsoft" type="button" role="tab">
                                <i class="fab fa-microsoft me-1"></i>Microsoft Data
                            </button>
                        </li>
                        @endif
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="raw-tab" data-bs-toggle="tab" data-bs-target="#raw" type="button" role="tab">
                                <i class="fas fa-code me-1"></i>Raw Data
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="userTabsContent">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Personal Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">Full Name:</td>
                                            <td>{{ $user->display_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">First Name:</td>
                                            <td>{{ $user->first_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Last Name:</td>
                                            <td>{{ $user->last_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Email:</td>
                                            <td>{{ $user->email }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">User Principal Name:</td>
                                            <td>{{ $user->user_principal_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">User Type:</td>
                                            <td>{{ $user->user_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Preferred Language:</td>
                                            <td>{{ $user->preferred_language ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Usage Location:</td>
                                            <td>{{ $user->usage_location ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Platform:</td>
                                            <td>
                                                <span class="badge bg-{{ $user->platform === 'microsoft' ? 'primary' : 'danger' }}">
                                                    {{ ucfirst($user->platform) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Account Status</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">Status:</td>
                                            <td>
                                                <span class="badge bg-{{ $user->account_enabled ? 'success' : 'danger' }}">
                                                    {{ $user->account_enabled ? 'Active' : 'Disabled' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">User Type:</td>
                                            <td>{{ $user->user_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Creation Type:</td>
                                            <td>{{ $user->creation_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Created Date:</td>
                                            <td>{{ $user->created_date_time ? $user->created_date_time->format('M j, Y g:i A') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">External State:</td>
                                            <td>{{ $user->external_user_state ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Employee ID:</td>
                                            <td>{{ $user->employee_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Employee Type:</td>
                                            <td>{{ $user->employee_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Hire Date:</td>
                                            <td>{{ $user->employee_hire_date ? $user->employee_hire_date->format('M j, Y') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Company Name:</td>
                                            <td>{{ $user->company_name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Tab -->
                        <div class="tab-pane fade" id="contact" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Phone Numbers</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">Work Phone:</td>
                                            <td>{{ $user->phone_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Mobile Phone:</td>
                                            <td>{{ $user->mobile_phone ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Work Tab -->
                        <div class="tab-pane fade" id="work" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Job Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">Job Title:</td>
                                            <td>{{ $user->job_title ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Department:</td>
                                            <td>{{ $user->department ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Company:</td>
                                            <td>{{ $user->company_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Employee ID:</td>
                                            <td>{{ $user->employee_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Employee Type:</td>
                                            <td>{{ $user->employee_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Hire Date:</td>
                                            <td>{{ $user->employee_hire_date ? $user->employee_hire_date->format('M j, Y') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Office Location:</td>
                                            <td>{{ $user->office_location ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Manager Email:</td>
                                            <td>{{ $user->manager_email ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Profile Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">About Me:</td>
                                            <td>{{ $user->about_me ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Interests:</td>
                                            <td>
                                                @if($user->interests && count($user->interests) > 0)
                                                    @foreach($user->interests as $interest)
                                                        <span class="badge bg-primary me-1">{{ $interest }}</span>
                                                    @endforeach
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Skills:</td>
                                            <td>
                                                @if($user->skills && count($user->skills) > 0)
                                                    @foreach($user->skills as $skill)
                                                        <span class="badge bg-success me-1">{{ $skill }}</span>
                                                    @endforeach
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Address Tab -->
                        <div class="tab-pane fade" id="address" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Physical Address</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">Street Address:</td>
                                            <td>{{ $user->street_address ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">City:</td>
                                            <td>{{ $user->city ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">State:</td>
                                            <td>{{ $user->state ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Postal Code:</td>
                                            <td>{{ $user->postal_code ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Country:</td>
                                            <td>{{ $user->country ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Location Settings</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold text-muted">Usage Location:</td>
                                            <td>{{ $user->usage_location ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Preferred Language:</td>
                                            <td>{{ $user->preferred_language ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Preferred Data Location:</td>
                                            <td>{{ $user->preferred_data_location ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>



                        <!-- Microsoft Data Tab -->
                        @if($user->platform === 'microsoft')
                        <div class="tab-pane fade" id="microsoft" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Assigned Licenses</h5>
                                    @php
                                        $assignedLicenses = is_string($user->assigned_licenses) ? json_decode($user->assigned_licenses, true) : $user->assigned_licenses;
                                    @endphp
                                    @if($assignedLicenses && is_array($assignedLicenses) && count($assignedLicenses) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>SKU ID</th>
                                                        <th>Disabled Plans</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($assignedLicenses as $license)
                                                    <tr>
                                                        <td><code>{{ $license['skuId'] ?? 'N/A' }}</code></td>
                                                        <td>
                                                            @if(isset($license['disabledPlans']) && count($license['disabledPlans']) > 0)
                                                                {{ implode(', ', $license['disabledPlans']) }}
                                                            @else
                                                                None
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No licenses assigned</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Assigned Plans</h5>
                                    @php
                                        $assignedPlans = is_string($user->assigned_plans) ? json_decode($user->assigned_plans, true) : $user->assigned_plans;
                                    @endphp
                                    @if($assignedPlans && is_array($assignedPlans) && count($assignedPlans) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>Service</th>
                                                        <th>Status</th>
                                                        <th>Assigned Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($assignedPlans as $plan)
                                                    <tr>
                                                        <td>{{ $plan['service'] ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $plan['capabilityStatus'] === 'Enabled' ? 'success' : 'warning' }}">
                                                                {{ $plan['capabilityStatus'] ?? 'Unknown' }}
                                                            </span>
                                                        </td>
                                                        <td>{{ isset($plan['assignedDateTime']) ? \Carbon\Carbon::parse($plan['assignedDateTime'])->format('M j, Y') : 'N/A' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No plans assigned</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Provisioned Plans</h5>
                                    @php
                                        $provisionedPlans = is_string($user->provisioned_plans) ? json_decode($user->provisioned_plans, true) : $user->provisioned_plans;
                                    @endphp
                                    @if($provisionedPlans && is_array($provisionedPlans) && count($provisionedPlans) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>Service</th>
                                                        <th>Status</th>
                                                        <th>Provisioning</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($provisionedPlans as $plan)
                                                    <tr>
                                                        <td>{{ $plan['service'] ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $plan['capabilityStatus'] === 'Enabled' ? 'success' : 'warning' }}">
                                                                {{ $plan['capabilityStatus'] ?? 'Unknown' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-{{ $plan['provisioningStatus'] === 'Success' ? 'success' : 'danger' }}">
                                                                {{ $plan['provisioningStatus'] ?? 'Unknown' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No provisioned plans</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Identities</h5>
                                    @php
                                        $identities = is_string($user->identities) ? json_decode($user->identities, true) : $user->identities;
                                    @endphp
                                    @if($identities && is_array($identities) && count($identities) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>Sign-in Type</th>
                                                        <th>Issuer</th>
                                                        <th>Issuer Assigned ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($identities as $identity)
                                                    <tr>
                                                        <td>{{ $identity['signInType'] ?? 'N/A' }}</td>
                                                        <td>{{ $identity['issuer'] ?? 'N/A' }}</td>
                                                        <td>{{ $identity['issuerAssignedId'] ?? 'N/A' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No identities found</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Raw Data Tab -->
                        <div class="tab-pane fade" id="raw" role="tabpanel">
                            <h5 class="mb-3">Complete Raw Data</h5>
                            <div class="bg-light p-3 rounded">
                                <pre class="mb-0" style="max-height: 500px; overflow-y: auto;"><code>{{ json_encode($user->raw_data, JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('sync.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>
</x-app-layout>

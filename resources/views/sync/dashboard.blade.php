<x-app-layout>
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-body py-4">
                    <h1 class="h4 fw-bold text-dark mb-2">
                        <i class="fas fa-cloud-upload-alt text-primary me-2"></i>
                        User Sync Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        Welcome back, <strong>{{ Auth::user()->name }}</strong>
                        @if(Auth::user()->hasRole('admin'))
                            <span class="badge bg-warning text-dark ms-2">
                                <i class="fas fa-shield-alt me-1"></i>Administrator
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4 stats-row">
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card stats-card border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users display-4 mb-3"></i>
                    <h2 class="display-6 fw-bold mb-1">{{ $stats['total_users'] }}</h2>
                    <p class="mb-0 opacity-75">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card stats-card border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-layer-group display-4 mb-3"></i>
                    <h2 class="display-6 fw-bold mb-1">{{ $stats['total_groups'] }}</h2>
                    <p class="mb-0 opacity-75">Total Groups</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm bg-white h-100">
                <div class="card-body text-center">
                    <i class="fab fa-microsoft text-primary display-4 mb-3"></i>
                    <h2 class="display-6 fw-bold text-primary mb-1">{{ $stats['microsoft_users'] }}</h2>
                    <p class="mb-0 text-muted">Microsoft Users</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm bg-white h-100">
                <div class="card-body text-center">
                    <i class="fab fa-google text-danger display-4 mb-3"></i>
                    <h2 class="display-6 fw-bold text-danger mb-1">{{ $stats['google_users'] }}</h2>
                    <p class="mb-0 text-muted">Google Users</p>
                </div>
            </div>
        </div>
    </div>


    <!-- Recent Users -->
    <div class="row mb-4">
        <div class="col-12" style="min-width: 0;">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people-fill me-2 text-primary"></i>
                        Recent Users
                    </h5>
                    <a href="{{ route('sync.export.users') }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-download me-1"></i>Export Users
                    </a>
                </div>
                <div class="card-body overflow-card">
                    @if($users->count() > 0)
                        <div class="table-responsive mb-0" style="min-width: 0;">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-person me-1"></i>Name</th>
                                        <th><i class="bi bi-envelope me-1"></i>Email</th>
                                        <th><i class="bi bi-cloud me-1"></i>Platform</th>
                                        <th><i class="bi bi-people me-1"></i>Group</th>
                                        <th><i class="bi bi-building me-1"></i>Department</th>
                                        <th><i class="bi bi-shield-check me-1"></i>Status</th>
                                        @if(Auth::user()->hasRole('admin'))
                                            <th><i class="bi bi-person-circle me-1"></i>Synced By</th>
                                        @endif
                                        <th><i class="bi bi-gear me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person-circle text-primary me-2"></i>
                                                    <strong>{{ $user->display_name }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="bi bi-envelope me-1 text-muted"></i>
                                                {{ $user->email }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $user->platform === 'microsoft' ? 'primary' : 'danger' }}">
                                                    <i class="bi bi-{{ $user->platform === 'microsoft' ? 'microsoft' : 'google' }} me-1"></i>
                                                    {{ ucfirst($user->platform) }}
                                                </span>
                                            </td>
                                            <td>
                                                <i class="bi bi-people me-1 text-muted"></i>
                                                {{ $user->group_name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <i class="bi bi-building me-1 text-muted"></i>
                                                {{ $user->department ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $user->account_enabled ? 'success' : 'danger' }}">
                                                    <i class="bi bi-{{ $user->account_enabled ? 'check-circle' : 'x-circle' }} me-1"></i>
                                                    {{ $user->account_enabled ? 'Active' : 'Disabled' }}
                                                </span>
                                            </td>
                                            @if(Auth::user()->hasRole('admin'))
                                                <td>
                                                    <i class="bi bi-person-circle me-1"></i>
                                                    {{ $user->user->name ?? 'Unknown' }}
                                                </td>
                                            @endif
                                            <td>
                                                <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
                            <i class="bi bi-people display-1 text-muted"></i>
                            <p class="text-muted mt-3">No users synced yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Groups -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-collection-fill me-2 text-primary"></i>
                        Recent Groups
                    </h5>
                    <a href="{{ route('sync.export.groups') }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-download me-1"></i>Export Groups
                    </a>
                </div>
                <div class="card-body">
                    @if($groups->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-collection me-1"></i>Name</th>
                                        <th><i class="bi bi-envelope me-1"></i>Email</th>
                                        <th><i class="bi bi-cloud me-1"></i>Platform</th>
                                        <th><i class="bi bi-tag me-1"></i>Type</th>
                                        <th><i class="bi bi-people me-1"></i>Members</th>
                                        @if(Auth::user()->hasRole('admin'))
                                            <th><i class="bi bi-person-circle me-1"></i>Synced By</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groups as $group)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-collection text-primary me-2"></i>
                                                    <strong>{{ $group->display_name }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="bi bi-envelope me-1 text-muted"></i>
                                                {{ $group->email ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $group->platform === 'microsoft' ? 'primary' : 'danger' }}">
                                                    <i class="bi bi-{{ $group->platform === 'microsoft' ? 'microsoft' : 'google' }} me-1"></i>
                                                    {{ ucfirst($group->platform) }}
                                                </span>
                                            </td>
                                            <td>
                                                <i class="bi bi-tag me-1 text-muted"></i>
                                                {{ $group->group_type ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-people me-1"></i>
                                                    {{ is_array($group->members) ? count($group->members) : ($group->members ?? 0) }}
                                                </span>
                                            </td>
                                            @if(Auth::user()->hasRole('admin'))
                                                <td>
                                                    <i class="bi bi-person-circle me-1"></i>
                                                    {{ $group->user->name ?? 'Unknown' }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $groups->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-collection display-1 text-muted"></i>
                            <p class="text-muted mt-3">No groups synced yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sync Activity -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        Recent Sync Activity
                    </h5>
                </div>
                <div class="card-body">
                    @if($syncLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-cloud me-1"></i>Platform</th>
                                        <th><i class="fas fa-cog me-1"></i>Type</th>
                                        <th><i class="fas fa-check-circle me-1"></i>Status</th>
                                        <th><i class="fas fa-users me-1"></i>Users</th>
                                        <th><i class="fas fa-layer-group me-1"></i>Groups</th>
                                        @if(Auth::user()->hasRole('admin'))
                                            <th><i class="fas fa-user me-1"></i>Synced By</th>
                                        @endif
                                        <th><i class="fas fa-calendar me-1"></i>Started</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($syncLogs as $log)
                                        <tr>
                                            <td>
                                                <span class="badge bg-{{ $log->platform === 'microsoft' ? 'primary' : 'danger' }}">
                                                    <i class="bi bi-{{ $log->platform === 'microsoft' ? 'microsoft' : 'google' }} me-1"></i>
                                                    {{ ucfirst($log->platform) }}
                                                </span>
                                            </td>
                                            <td class="text-capitalize">{{ $log->sync_type }}</td>
                                            <td>
                                                <span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">
                                                    <i class="bi bi-{{ $log->status === 'completed' ? 'check-circle' : ($log->status === 'failed' ? 'x-circle' : 'clock') }} me-1"></i>
                                                    {{ ucfirst($log->status) }}
                                                </span>
                                            </td>
                                            <td><span class="badge bg-info">{{ $log->users_synced }}</span></td>
                                            <td><span class="badge bg-info">{{ $log->groups_synced }}</span></td>
                                            @if(Auth::user()->hasRole('admin'))
                                                <td>
                                                    <i class="bi bi-person-circle me-1"></i>
                                                    {{ $log->user->name ?? 'Unknown' }}
                                                </td>
                                            @endif
                                            <td>
                                                <small class="text-muted">
                                                    {{ $log->started_at->format('M j, Y g:i A') }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="text-muted mt-3">No sync activity yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

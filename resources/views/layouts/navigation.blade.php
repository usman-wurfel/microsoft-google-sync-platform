<nav class="navbar navbar-expand-lg navbar-dark navbar-app">
    <div class="container-fluid">
        <!-- Sidebar Toggle (mobile) -->
        <button class="navbar-toggler me-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('sync.dashboard') }}">
            @if($appSettings->logo_path ?? false)
                <img src="{{ asset($appSettings->logo_path) }}" alt="Logo" style="height: 32px; width: auto; margin-right: 8px;">
            @endif
        </a>

        <div class="d-flex align-items-center ms-auto">
            <!-- User Dropdown -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2" style="font-size: 2.5rem;"></i>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold">{{ Auth::user()->name }}</span>
                            @if(Auth::user()->hasRole('admin'))
                                <small class="text-warning"><i class="fas fa-shield-alt me-1"></i>Admin</small>
                            @else
                                <small class="text-muted">User</small>
                            @endif
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger" onclick="confirmLogout(event)">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('appSidebar');
    var btn = document.getElementById('sidebarToggle');
    if (sidebar && btn) {
        btn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !btn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }
});
</script>

<script>
function confirmLogout(event) {
    event.preventDefault();
    var form = document.getElementById('logout-form') || document.getElementById('sidebar-logout-form') || event.target.closest('form');
    if (!form) return;
    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of the system!",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) { caches.delete(name); }
                });
            }
            form.submit();
        }
    });
}
</script>


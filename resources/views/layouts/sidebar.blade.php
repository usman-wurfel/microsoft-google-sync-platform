{{-- Sidebar: Dashboard, Company Integrations, Graph API Mail, Report Phishing, Automated Allowlisting, Role Management, Settings, Profile, Logout --}}
<aside class="app-sidebar" id="appSidebar">
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('sync.dashboard') ? 'active' : '' }}" href="{{ route('sync.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('integrations.index') ? 'active' : '' }}" href="{{ route('integrations.index') }}">
                    <i class="fas fa-th-large me-2"></i>Company Integrations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('integrations.graph-mail.*') ? 'active' : '' }}" href="{{ route('integrations.graph-mail.test') }}">
                    <i class="fas fa-envelope me-2"></i>Graph API Mail
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('integrations.phishing.dashboard') ? 'active' : '' }}" href="{{ route('integrations.phishing.dashboard') }}">
                    <i class="fas fa-shield-alt me-2"></i>Report Phishing
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('integrations.phishing.test') ? 'active' : '' }}" href="{{ route('integrations.phishing.test') }}">
                    <i class="fas fa-vial me-2"></i>Phishing Test
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('integrations.allowlist.*') ? 'active' : '' }}" href="{{ route('integrations.allowlist.test') }}">
                    <i class="fas fa-lock-open me-2"></i>Automated Allowlisting
                </a>
            </li>
            @role('admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                    <i class="fas fa-user-tag me-2"></i>Role Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            @endrole
        </ul>
        <ul class="nav flex-column mt-auto sidebar-bottom">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile.edit') }}">
                    <i class="fas fa-user me-2"></i>Profile
                </a>
            </li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form" class="d-inline">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start d-flex align-items-center text-danger" onclick="return confirmLogout(event)">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </button>
                </form>
            </li>
        </ul>
    </nav>
</aside>

<style>
.app-sidebar {
    width: 260px;
    height: calc(100vh - 79px);
    max-height: calc(100vh - 79px);
    background: rgba(30, 30, 46, 0.98);
    color: #a0aec0;
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 79px;
    z-index: 1030;
    box-shadow: 4px 0 15px rgba(0,0,0,0.15);
    transition: transform 0.25s ease, width 0.25s ease;
}
.sidebar-nav {
    flex: 1;
    min-height: 0;
    padding: 1rem 0 0.75rem;
    overflow-y: auto;
    overflow-x: hidden;
}
.sidebar-nav .nav-link {
    color: #a0aec0;
    padding: 0.6rem 1.25rem;
    border-radius: 0 8px 8px 0;
    margin: 0 0.5rem 0 0;
    display: flex;
    align-items: center;
}
.sidebar-nav .nav-link:hover { color: #fff; background: rgba(255,255,255,0.08); }
.sidebar-nav .nav-link.active { color: #fff; background: linear-gradient(90deg, rgba(102,126,234,0.4) 0%, rgba(118,75,162,0.3) 100%); }
.sidebar-bottom { border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.75rem; }
@media (max-width: 991.98px) {
    .app-sidebar {
        top: 79px;
        transform: translateX(-100%);
        height: calc(100vh - 79px);
    }
    .app-sidebar.show { transform: translateX(0); }
    .main-with-sidebar { margin-left: 0 !important; }
}
</style>

{{-- Navbar Component --}}
<nav class="navbar">
    <div class="navbar-left">
        <button class="btn-toggle-sidebar" id="btn-toggle-sidebar" type="button" aria-label="Toggle sidebar">
            <i class="bi bi-list" style="font-size:24px"></i>
        </button>
        <span class="page-title">@yield('page-title', 'Dashboard')</span>
    </div>

    <div class="navbar-right">
        <button class="navbar-toggle-btn" id="btn-toggle-font" type="button" title="Ubah ukuran font">
            <span id="font-size-label" style="font-weight:700;font-size:14px">Aa</span>
        </button>

        <button class="navbar-toggle-btn" id="btn-toggle-theme" type="button" title="Mode gelap / terang">
            <span id="theme-icon">Dark</span>
        </button>

        <div class="user-dropdown">
            <button class="user-dropdown-btn" id="user-dropdown-btn" type="button">
                <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                <span>{{ auth()->user()->name ?? 'User' }}</span>
                <i class="bi bi-chevron-down" style="font-size:12px;opacity:.6"></i>
            </button>
            <div class="user-dropdown-menu" id="user-dropdown-menu">
                <div style="padding:10px 16px;border-bottom:1px solid var(--border-color)">
                    <div style="font-weight:600;font-size:var(--font-size-sm)">{{ auth()->user()->name ?? '' }}</div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted)">{{ auth()->user()->role->nama_role ?? '' }}</div>
                </div>
                <a href="{{ route('profile.edit') }}">
                    <i class="bi bi-person"></i>
                    Profil Saya
                </a>
                <a href="{{ route('profile.password.edit') }}">
                    <i class="bi bi-key"></i>
                    Ganti Password
                </a>
                <div class="dropdown-divider"></div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

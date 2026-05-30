{{-- Sidebar Component - role final sekolah --}}
@php
    $user = auth()->user();
    $role = $user->role->nama_role ?? '';
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $dashboardRoute = $user->dashboardRouteName() ?? 'dashboard';
    $pengaturanSekolah = \App\Models\PengaturanSekolah::getSettings();

    $menus = [
        ['label' => 'UTAMA', 'type' => 'section'],
        ['route' => $dashboardRoute, 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'roles' => ['Admin','Guru','Kepala Sekolah'], 'activeRoutes' => ['dashboard', 'dashboard.*']],

        ['label' => 'MANAJEMEN DATA', 'type' => 'section', 'roles' => ['Admin']],
        ['route' => 'users.index', 'label' => 'Manajemen User', 'icon' => 'bi-people-fill', 'roles' => ['Admin']],
        ['route' => 'guru.index', 'label' => 'Data Guru', 'icon' => 'bi-person-badge-fill', 'roles' => ['Admin']],
        ['route' => 'siswa.index', 'label' => 'Data Siswa', 'icon' => 'bi-mortarboard-fill', 'roles' => ['Admin']],
        ['route' => 'kelas.index', 'label' => 'Data Kelas', 'icon' => 'bi-building', 'roles' => ['Admin']],
        ['route' => 'mapel.index', 'label' => 'Mata Pelajaran', 'icon' => 'bi-book-fill', 'roles' => ['Admin']],
        ['route' => 'tahun-ajaran.index', 'label' => 'Tahun Ajaran', 'icon' => 'bi-calendar3', 'roles' => ['Admin']],

        ['label' => 'KELAS SAYA', 'type' => 'section', 'roles' => ['Guru']],
        ['route' => 'kelas.index', 'label' => 'Data Kelas Saya', 'icon' => 'bi-building', 'roles' => ['Guru']],
        ['route' => 'siswa.index', 'label' => 'Siswa Kelas Saya', 'icon' => 'bi-mortarboard-fill', 'roles' => ['Guru']],

        ['label' => 'UJIAN', 'type' => 'section', 'roles' => ['Admin','Guru']],
        ['route' => 'ujian.index', 'label' => 'Data Ujian', 'icon' => 'bi-file-earmark-text-fill', 'roles' => ['Admin','Guru']],
        ['route' => 'ujian.index', 'label' => 'Kunci Jawaban', 'icon' => 'bi-key-fill', 'roles' => ['Admin','Guru'], 'customActive' => 'kunci-jawaban'],

        ['label' => 'PROSES ANALISIS', 'type' => 'section', 'roles' => ['Admin','Guru']],
        ['route' => 'ujian.index', 'label' => 'Data Mentah T1', 'icon' => 'bi-table', 'roles' => ['Admin','Guru'], 'customActive' => 'data-mentah'],
        ['route' => 'ujian.index', 'label' => 'Olah Data T2', 'icon' => 'bi-bar-chart-line-fill', 'roles' => ['Admin','Guru'], 'customActive' => 'olah-data'],
        ['route' => 'ujian.index', 'label' => 'Analisis Data T3', 'icon' => 'bi-clipboard-data-fill', 'roles' => ['Admin','Guru'], 'customActive' => 'analisis-data'],

        ['label' => 'LAPORAN', 'type' => 'section', 'roles' => ['Admin','Guru','Kepala Sekolah']],
        ['route' => 'ujian.index', 'label' => 'Laporan Analisis T3', 'icon' => 'bi-clipboard-data-fill', 'roles' => ['Kepala Sekolah'], 'customActive' => 'analisis-data'],
        ['route' => 'ujian.index', 'label' => 'Daftar Nilai T4', 'icon' => 'bi-journal-text', 'roles' => ['Admin','Guru','Kepala Sekolah'], 'customActive' => 'daftar-nilai'],
        ['route' => 'ujian.index', 'label' => 'Rekap Nilai T5', 'icon' => 'bi-file-earmark-bar-graph-fill', 'roles' => ['Admin','Guru','Kepala Sekolah'], 'customActive' => 'rekap-nilai'],
        ['route' => 'ujian.index', 'label' => 'Laporan Export', 'icon' => 'bi-download', 'roles' => ['Admin','Guru','Kepala Sekolah'], 'customActive' => 'export'],
        ['route' => 'audit-log.index', 'label' => 'Riwayat Aktivitas', 'icon' => 'bi-clock-history', 'roles' => ['Admin','Kepala Sekolah'], 'activeRoutes' => ['audit-log.*', 'audit-trail.*']],

        ['label' => 'DATA READ-ONLY', 'type' => 'section', 'roles' => ['Kepala Sekolah']],
        ['route' => 'guru.index', 'label' => 'Data Guru Read-only', 'icon' => 'bi-person-badge-fill', 'roles' => ['Kepala Sekolah']],
        ['route' => 'siswa.index', 'label' => 'Data Siswa Read-only', 'icon' => 'bi-mortarboard-fill', 'roles' => ['Kepala Sekolah']],
        ['route' => 'kelas.index', 'label' => 'Data Kelas Read-only', 'icon' => 'bi-building', 'roles' => ['Kepala Sekolah']],

        ['label' => 'PENGATURAN', 'type' => 'section', 'roles' => ['Admin','Guru','Kepala Sekolah']],
        ['route' => 'profil-sekolah.edit', 'label' => 'Profil Sekolah', 'icon' => 'bi-gear-fill', 'roles' => ['Admin']],
        ['route' => 'profile.edit', 'label' => 'Profil Saya', 'icon' => 'bi-person-circle', 'roles' => ['Admin','Guru','Kepala Sekolah'], 'activeRoutes' => ['profile.*']],
    ];
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-row">
            @if($pengaturanSekolah->logoUrl())
                <img src="{{ $pengaturanSekolah->logoUrl() }}" alt="Logo sekolah">
            @else
                <span class="sidebar-brand-mark"><i class="bi bi-clipboard-data-fill"></i></span>
            @endif
            <div>
                <h2>SIABSoal</h2>
                <small>SMPN 2 Tasikmalaya</small>
            </div>
        </div>
    </div>

    <ul class="sidebar-nav">
        @foreach ($menus as $menu)
            @if (isset($menu['type']) && $menu['type'] === 'section')
                @if (!isset($menu['roles']) || in_array($role, $menu['roles'], true))
                    <li class="nav-label">{{ $menu['label'] }}</li>
                @endif
            @else
                @if (in_array($role, $menu['roles'] ?? [], true))
                    @php
                        $isActive = false;
                        if (isset($menu['customActive'])) {
                            $isActive = request()->routeIs($menu['customActive'] . '.*') || request()->is('*' . $menu['customActive'] . '*');
                        } elseif (isset($menu['activeRoutes'])) {
                            foreach ($menu['activeRoutes'] as $pattern) {
                                $isActive = $isActive || request()->routeIs($pattern);
                            }
                        } else {
                            $isActive = request()->routeIs(explode('.', $menu['route'])[0] . '.*') || $currentRoute === $menu['route'];
                        }
                        $href = '#';
                        try {
                            $href = route($menu['route']);
                        } catch (\Exception $e) {
                            $href = '#';
                        }
                    @endphp
                    <li class="nav-item">
                        <a href="{{ $href }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                            <i class="bi {{ $menu['icon'] }} nav-icon"></i>
                            <span>{{ $menu['label'] }}</span>
                        </a>
                    </li>
                @endif
            @endif
        @endforeach
    </ul>
</aside>

{{-- Sidebar Component — Menu Final Sesuai Arahan Sekolah --}}
@php
    $user = auth()->user();
    $role = $user->role->nama_role ?? '';
    $currentRoute = request()->route() ? request()->route()->getName() : '';

    // Menu items sesuai arahan sekolah
    $menus = [
        ['label' => 'UTAMA', 'type' => 'section'],
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'roles' => ['Admin','Guru','Operator','Wakil Kurikulum']],

        ['label' => 'MANAJEMEN DATA', 'type' => 'section', 'roles' => ['Admin','Operator']],
        ['route' => 'users.index', 'label' => 'Manajemen User', 'icon' => 'bi-people-fill', 'roles' => ['Admin']],
        ['route' => 'guru.index', 'label' => 'Data Guru', 'icon' => 'bi-person-badge-fill', 'roles' => ['Admin']],
        ['route' => 'siswa.index', 'label' => 'Data Siswa', 'icon' => 'bi-mortarboard-fill', 'roles' => ['Admin','Operator']],
        ['route' => 'kelas.index', 'label' => 'Data Kelas', 'icon' => 'bi-building', 'roles' => ['Admin','Operator']],
        ['route' => 'mapel.index', 'label' => 'Mata Pelajaran', 'icon' => 'bi-book-fill', 'roles' => ['Admin','Operator']],
        ['route' => 'tahun-ajaran.index', 'label' => 'Tahun Ajaran', 'icon' => 'bi-calendar3', 'roles' => ['Admin']],

        ['label' => 'UJIAN', 'type' => 'section'],
        ['route' => 'ujian.index', 'label' => 'Data Ujian', 'icon' => 'bi-file-earmark-text-fill', 'roles' => ['Admin','Guru']],
        ['route' => 'ujian.index', 'label' => 'Kunci Jawaban', 'icon' => 'bi-key-fill', 'roles' => ['Admin','Guru'], 'customActive' => 'kunci-jawaban'],

        ['label' => 'PROSES ANALISIS', 'type' => 'section'],
        ['route' => 'ujian.index', 'label' => 'Data Mentah T1', 'icon' => 'bi-table', 'roles' => ['Admin','Guru','Operator'], 'customActive' => 'data-mentah'],
        ['route' => 'ujian.index', 'label' => 'Olah Data T2', 'icon' => 'bi-bar-chart-line-fill', 'roles' => ['Admin','Guru'], 'customActive' => 'olah-data'],
        ['route' => 'ujian.index', 'label' => 'Analisis Data T3', 'icon' => 'bi-clipboard-data-fill', 'roles' => ['Admin','Guru'], 'customActive' => 'analisis-data'],

        ['label' => 'LAPORAN', 'type' => 'section'],
        ['route' => 'ujian.index', 'label' => 'Daftar Nilai T4', 'icon' => 'bi-journal-text', 'roles' => ['Admin','Guru','Operator','Wakil Kurikulum'], 'customActive' => 'daftar-nilai'],
        ['route' => 'ujian.index', 'label' => 'Rekap Nilai T5', 'icon' => 'bi-file-earmark-bar-graph-fill', 'roles' => ['Admin','Guru','Operator','Wakil Kurikulum'], 'customActive' => 'rekap-nilai'],
        ['route' => 'ujian.index', 'label' => 'Laporan Export', 'icon' => 'bi-download', 'roles' => ['Admin','Guru','Wakil Kurikulum'], 'customActive' => 'export'],

        ['label' => 'PENGATURAN', 'type' => 'section', 'roles' => ['Admin']],
        ['route' => 'profil-sekolah.edit', 'label' => 'Profil Sekolah', 'icon' => 'bi-gear-fill', 'roles' => ['Admin']],
    ];
@endphp

<aside class="sidebar" id="sidebar">
    {{-- Brand --}}
    <div class="sidebar-brand">
        <h2>SIABSoal</h2>
        <small>SMPN 2 Tasikmalaya</small>
    </div>

    {{-- Navigation --}}
    <ul class="sidebar-nav">
        @foreach ($menus as $menu)
            @if (isset($menu['type']) && $menu['type'] === 'section')
                {{-- Section header --}}
                @if (!isset($menu['roles']) || in_array($role, $menu['roles']))
                    <li class="nav-label">{{ $menu['label'] }}</li>
                @endif
            @else
                {{-- Menu item --}}
                @if (in_array($role, $menu['roles'] ?? []))
                    @php
                        $isActive = false;
                        if (isset($menu['customActive'])) {
                            $isActive = request()->routeIs($menu['customActive'] . '.*') || request()->is('*' . $menu['customActive'] . '*');
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

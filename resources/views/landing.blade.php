<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SIABSoal SMPN 2 Tasikmalaya - Sistem Informasi Analisis Butir Soal Berbasis Web untuk DP, TK, daftar nilai, dan rekap nilai.">
    <title>SIABSoal SMPN 2 Tasikmalaya - Sistem Informasi Analisis Butir Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script>
        (function(){
            var t = localStorage.getItem('siabsoal_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
    <div class="landing-wrapper">
        <nav class="landing-nav">
            <a href="#beranda" class="landing-brand">
                @if($pengaturan->logoUrl())
                    <img src="{{ $pengaturan->logoUrl() }}" alt="Logo sekolah">
                @else
                    <span class="landing-brand-mark"><i class="bi bi-clipboard-data-fill"></i></span>
                @endif
                <span>SIABSoal</span>
            </a>

            <div class="landing-menu">
                <a href="#beranda">Beranda</a>
                <a href="#fitur">Fitur</a>
                <a href="#alur">Alur</a>
                <a href="#tentang">Tentang</a>
            </div>

            <div class="landing-actions">
                <button class="navbar-toggle-btn" id="btn-toggle-theme" type="button" title="Mode gelap / terang">
                    <span id="theme-icon">Dark</span>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                @endauth
            </div>
        </nav>

        <main>
            <section class="landing-hero" id="beranda">
                <div class="landing-hero-inner">
                    <div class="landing-copy">
                        <span class="landing-kicker">{{ $pengaturan->nama_sekolah ?? 'SMP NEGERI 2 TASIKMALAYA' }}</span>
                        <h1>SIABSoal SMPN 2 Tasikmalaya</h1>
                        <h2>Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya</h2>
                        <p>
                            Membantu guru mengolah jawaban siswa, membentuk kelompok atas dan bawah,
                            menghitung daya pembeda dan tingkat kesukaran, serta menyusun daftar nilai
                            dan rekap nilai secara cepat dan terpusat.
                        </p>
                        <div class="landing-cta">
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                                    <i class="bi bi-grid-1x2-fill"></i> Masuk Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Login ke Sistem
                                </a>
                            @endauth
                            <a href="#fitur" class="btn btn-outline btn-lg">
                                <i class="bi bi-arrow-down-circle"></i> Pelajari Fitur
                            </a>
                        </div>
                    </div>

                    <div class="landing-feature-panel" aria-label="Fitur utama SIABSoal">
                        @foreach([
                            ['icon' => 'bi-table', 'label' => 'Data Mentah 0/1'],
                            ['icon' => 'bi-diagram-3-fill', 'label' => 'Olah Kelompok Atas & Bawah'],
                            ['icon' => 'bi-clipboard-data-fill', 'label' => 'Analisis DP dan TK'],
                            ['icon' => 'bi-journal-text', 'label' => 'Daftar Nilai'],
                            ['icon' => 'bi-file-earmark-bar-graph-fill', 'label' => 'Rekap Nilai'],
                            ['icon' => 'bi-download', 'label' => 'Export PDF & Excel'],
                        ] as $feature)
                            <div class="landing-feature-chip">
                                <i class="bi {{ $feature['icon'] }}"></i>
                                <span>{{ $feature['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="landing-section" id="fitur">
                <div class="landing-section-heading">
                    <span>Fitur Utama</span>
                    <h2>Fokus pada alur T1 sampai T5</h2>
                </div>
                <div class="landing-card-grid">
                    @foreach([
                        ['icon' => 'bi-table', 'title' => 'Data Mentah T1', 'desc' => 'Input atau import jawaban siswa dalam format A/B/C/D/E atau skor 0/1.'],
                        ['icon' => 'bi-bar-chart-line-fill', 'title' => 'Olah Data T2', 'desc' => 'Mengurutkan nilai dan membentuk kelompok atas serta bawah tanpa overlap.'],
                        ['icon' => 'bi-clipboard-data-fill', 'title' => 'Analisis Data T3', 'desc' => 'Menghitung Daya Pembeda (DP) dan Tingkat Kesukaran (TK).'],
                        ['icon' => 'bi-journal-text', 'title' => 'Daftar Nilai T4', 'desc' => 'Menyajikan nilai peserta didik secara rapi untuk guru.'],
                        ['icon' => 'bi-file-earmark-bar-graph-fill', 'title' => 'Rekap Nilai T5', 'desc' => 'Merangkum kehadiran, rentang nilai, ketuntasan, dan kualitas soal.'],
                        ['icon' => 'bi-file-earmark-arrow-down', 'title' => 'Export Laporan', 'desc' => 'Mengunduh laporan PDF dan Excel untuk dokumentasi sekolah.'],
                    ] as $item)
                        <article class="landing-info-card">
                            <div class="landing-info-icon"><i class="bi {{ $item['icon'] }}"></i></div>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="landing-section landing-flow-section" id="alur">
                <div class="landing-section-heading">
                    <span>Alur Sistem</span>
                    <h2>Dari jawaban siswa sampai rekap nilai</h2>
                </div>
                <div class="landing-flow">
                    @foreach([
                        ['step' => 'T1', 'title' => 'Jawaban ke 0/1'],
                        ['step' => 'T2', 'title' => 'Kelompok Atas & Bawah'],
                        ['step' => 'T3', 'title' => 'Analisis DP & TK'],
                        ['step' => 'T4', 'title' => 'Daftar Nilai'],
                        ['step' => 'T5', 'title' => 'Rekap Nilai'],
                    ] as $flow)
                        <div class="landing-step">
                            <strong>{{ $flow['step'] }}</strong>
                            <span>{{ $flow['title'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="landing-section" id="tentang">
                <div class="landing-benefits">
                    <div>
                        <span class="landing-kicker">Manfaat Sistem</span>
                        <h2>Dirancang untuk kebutuhan evaluasi guru dan laporan sekolah</h2>
                        <p>
                            SIABSoal membantu proses analisis butir soal tetap sederhana, terpusat,
                            dan mudah ditelusuri dari data mentah sampai rekap akhir.
                        </p>
                    </div>
                    <ul>
                        <li><i class="bi bi-check-circle-fill"></i> Mempermudah guru mengolah jawaban siswa</li>
                        <li><i class="bi bi-check-circle-fill"></i> Analisis DP dan TK lebih cepat</li>
                        <li><i class="bi bi-check-circle-fill"></i> Rekap nilai lebih rapi dan konsisten</li>
                        <li><i class="bi bi-check-circle-fill"></i> Data ujian tersimpan terpusat</li>
                        <li><i class="bi bi-check-circle-fill"></i> Laporan mudah diexport ke PDF dan Excel</li>
                    </ul>
                </div>
            </section>
        </main>

        <footer class="landing-footer">
            <span>&copy; 2026 SIABSoal SMPN 2 Tasikmalaya</span>
            <span>Kerja Praktik Teknik Informatika</span>
        </footer>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

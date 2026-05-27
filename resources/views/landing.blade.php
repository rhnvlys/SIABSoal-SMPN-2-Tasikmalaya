<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SIABSoal SMPN 2 Tasikmalaya — Sistem Informasi Analisis Butir Soal Berbasis Web untuk evaluasi kualitas soal dan rekap nilai.">
    <title>SIABSoal SMPN 2 Tasikmalaya — Sistem Informasi Analisis Butir Soal</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        (function(){
            var t = localStorage.getItem('siabsoal_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
    <div class="landing-wrapper">
        {{-- Navbar --}}
        <nav class="landing-nav">
            <div class="brand">
                <i class="bi bi-clipboard-data-fill"></i>
                SIABSoal
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <button class="navbar-toggle-btn" id="btn-toggle-theme" type="button" title="Mode gelap / terang">
                    <span id="theme-icon">🌙</span>
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

        {{-- Hero --}}
        <section class="landing-hero">
            <div class="landing-hero-inner">
                <div>
                    <h1>SIABSoal<br>SMPN 2 Tasikmalaya</h1>
                    <h2>Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya</h2>
                    <p>
                        Sistem berbasis web untuk membantu guru dalam mengolah jawaban siswa,
                        membentuk kelompok atas dan bawah, menghitung daya pembeda,
                        menghitung tingkat kesukaran, membuat daftar nilai, dan membuat
                        rekap nilai secara lebih cepat dan terpusat.
                    </p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-grid-1x2-fill"></i> Masuk ke Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login ke Sistem
                        </a>
                    @endauth
                </div>

                {{-- Feature List --}}
                <div class="feature-grid">
                    <div class="feature-item">
                        <div class="feature-icon stat-icon blue">
                            <i class="bi bi-table"></i>
                        </div>
                        <span>Data Mentah 0/1</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon stat-icon purple">
                            <i class="bi bi-bar-chart-line-fill"></i>
                        </div>
                        <span>Olah Kelompok Atas & Bawah</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon stat-icon green">
                            <i class="bi bi-clipboard-data-fill"></i>
                        </div>
                        <span>Analisis DP dan TK</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon stat-icon teal">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <span>Daftar Nilai</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon stat-icon amber">
                            <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        </div>
                        <span>Rekap Nilai</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon stat-icon red">
                            <i class="bi bi-download"></i>
                        </div>
                        <span>Export PDF & Excel</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="landing-footer">
            &copy; {{ date('Y') }} SIABSoal SMPN 2 Tasikmalaya — Kerja Praktik Teknik Informatika
        </footer>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

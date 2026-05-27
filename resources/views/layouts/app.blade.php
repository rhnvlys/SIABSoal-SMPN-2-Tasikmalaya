<!doctype html>
<html lang="id" data-theme="light" data-font-size="normal">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya">

    <title>@yield('title', 'Dashboard') — SIABSoal SMPN 2 Tasikmalaya</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- Restore theme before paint -->
    <script>
        (function(){
            var t = localStorage.getItem('siabsoal_theme') || 'light';
            var f = localStorage.getItem('siabsoal_font_size') || 'normal';
            document.documentElement.setAttribute('data-theme', t);
            document.documentElement.setAttribute('data-font-size', f);
        })();
    </script>

    @stack('styles')
</head>
<body>
    <div class="app-wrapper">
        {{-- Sidebar --}}
        @include('components.sidebar')

        {{-- Sidebar Overlay (mobile) --}}
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        {{-- Main Content --}}
        <div class="main-content">
            {{-- Navbar --}}
            @include('components.navbar')

            {{-- Page Content --}}
            <div class="page-content">
                {{-- Alerts --}}
                @include('components.alert')

                {{-- Yield Content --}}
                @yield('content')
            </div>
        </div>
    </div>

    {{-- Confirm Delete Modal --}}
    @include('components.modal-confirm')

    <!-- App JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

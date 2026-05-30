<!doctype html>
<html lang="id" data-theme="light" data-font-size="normal">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Login — Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya">

    <title>@yield('title', 'Login') — SIABSoal SMPN 2 Tasikmalaya</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <script>
        (function(){
            var t = localStorage.getItem('siabsoal_theme') || 'light';
            var f = localStorage.getItem('siabsoal_font_size') || 'normal';
            document.documentElement.setAttribute('data-theme', t);
            document.documentElement.setAttribute('data-font-size', f);
        })();
    </script>
</head>
<body>
    <div class="auth-wrapper">
        @yield('content')
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

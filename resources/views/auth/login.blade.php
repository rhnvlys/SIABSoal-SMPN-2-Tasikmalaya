@extends('layouts.auth')

@section('title', 'Login')

@section('content')
@php($pengaturan = \App\Models\PengaturanSekolah::getSettings())
<div class="auth-card">
    <div class="auth-logo">
        @if($pengaturan->logoUrl())
            <img src="{{ $pengaturan->logoUrl() }}" alt="Logo sekolah" class="auth-school-logo">
        @else
            <div class="auth-logo-fallback"><i class="bi bi-clipboard-data-fill"></i></div>
        @endif
        <h1>{{ $pengaturan->nama_sistem ?? 'SIABSoal' }}</h1>
        <p>{{ $pengaturan->nama_lengkap_sistem ?? 'Sistem Informasi Analisis Butir Soal' }}<br>{{ $pengaturan->nama_sekolah ?? 'SMPN 2 Tasikmalaya' }}</p>
    </div>

    {{-- Alert --}}
    @include('components.alert')

    <form action="{{ route('login.post') }}" method="POST">
        @csrf

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username"
                   value="{{ old('username') }}" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password"
                   required>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg" style="margin-top:8px">
            <i class="bi bi-box-arrow-in-right"></i>
            Login
        </button>
    </form>

    <div style="text-align:center;margin-top:20px">
        <a href="{{ route('landing') }}" style="font-size:var(--font-size-sm);color:var(--text-muted)">
            <i class="bi bi-arrow-left"></i> Kembali ke beranda
        </a>
    </div>
</div>
@endsection

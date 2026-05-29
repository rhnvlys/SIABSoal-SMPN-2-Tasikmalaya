@extends('layouts.app')

@section('title', 'Ganti Password')
@section('page-title', 'Ganti Password')

@section('content')
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Profil Saya', 'route' => 'profile.edit'],
    ['label' => 'Ganti Password'],
]])

<div class="page-header">
    <h1>Ganti Password</h1>
    <p>Gunakan password baru minimal 8 karakter.</p>
</div>

<div class="card" style="max-width:560px">
    <div class="card-body">
        <form action="{{ route('profile.password.update') }}" method="POST" data-loading data-loading-text="Memproses...">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Password Lama <span class="required">*</span></label>
                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Password Baru <span class="required">*</span></label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="8">
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Password</button>
                <a href="{{ route('profile.edit') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Profil Saya'],
]])

<div class="page-header">
    <h1>Profil Saya</h1>
    <p>Kelola informasi akun yang digunakan untuk masuk ke SIABSoal.</p>
</div>

<div class="card" style="max-width:720px">
    <div class="card-body">
        <form action="{{ route('profile.update') }}" method="POST" data-loading data-loading-text="Memproses...">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                    <small class="text-muted">Username hanya dapat diubah oleh admin.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="{{ $user->role->nama_role ?? '-' }}" disabled>
                </div>
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Profil</button>
                <a href="{{ route('profile.password.edit') }}" class="btn btn-outline"><i class="bi bi-key"></i> Ganti Password</a>
            </div>
        </form>
    </div>
</div>
@endsection

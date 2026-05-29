@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="page-header">
    <h1>Edit User</h1>
    <p>Ubah data pengguna: {{ $user->name }}</p>
</div>

<div class="card" style="max-width:680px">
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST" data-loading data-loading-text="Memproses...">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Username <span class="required">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru <span class="text-muted text-sm">(opsional untuk reset)</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="form-control" minlength="8">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Role <span class="required">*</span></label>
                    <select name="role_id" class="form-control" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->nama_role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status <span class="required">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="aktif" {{ old('status', $user->status) == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ old('status', $user->status) == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Perbarui</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>

<div class="card" style="max-width:680px;margin-top:24px">
    <div class="card-header">
        <i class="bi bi-key"></i> Reset Password User
    </div>
    <div class="card-body">
        <form action="{{ route('users.reset-password', $user) }}" method="POST" data-loading data-loading-text="Memproses...">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password Baru <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                </div>
            </div>
            <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-clockwise"></i> Reset Password</button>
        </form>
    </div>
</div>
@endsection

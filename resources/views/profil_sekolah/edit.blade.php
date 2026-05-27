@extends('layouts.app')
@section('title', 'Profil Sekolah')
@section('page-title', 'Profil Sekolah')
@section('content')
<div class="page-header"><h1>Profil Sekolah</h1><p>Atur informasi sekolah untuk laporan dan export</p></div>
<div class="card" style="max-width:680px"><div class="card-body">
    <form action="{{ route('profil-sekolah.update') }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="form-group"><label class="form-label">Nama Sekolah <span class="required">*</span></label><input type="text" name="nama_sekolah" class="form-control" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah) }}" required></div>
        <div class="form-group"><label class="form-label">Nama Sistem <span class="required">*</span></label><input type="text" name="nama_sistem" class="form-control" value="{{ old('nama_sistem', $pengaturan->nama_sistem) }}" required></div>
        <div class="form-group"><label class="form-label">Nama Lengkap Sistem <span class="required">*</span></label><input type="text" name="nama_lengkap_sistem" class="form-control" value="{{ old('nama_lengkap_sistem', $pengaturan->nama_lengkap_sistem) }}" required></div>
        <div class="form-group"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $pengaturan->alamat) }}</textarea></div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Kepala Sekolah</label><input type="text" name="kepala_sekolah" class="form-control" value="{{ old('kepala_sekolah', $pengaturan->kepala_sekolah) }}"></div>
            <div class="form-group"><label class="form-label">NIP Kepala Sekolah</label><input type="text" name="nip_kepala_sekolah" class="form-control" value="{{ old('nip_kepala_sekolah', $pengaturan->nip_kepala_sekolah) }}"></div>
        </div>
        <div class="form-group"><label class="form-label">Logo Sekolah</label><input type="file" name="logo" class="form-control" accept="image/png,image/jpeg">
            @if($pengaturan->logo)<p class="text-sm text-muted mt-1">Logo saat ini sudah diupload.</p>@endif</div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button></div>
    </form>
</div></div>
@endsection

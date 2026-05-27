@extends('layouts.app')
@section('title', 'Tambah Siswa')
@section('page-title', 'Tambah Siswa')
@section('content')
<div class="page-header"><h1>Tambah Siswa</h1></div>
<div class="card" style="max-width:680px"><div class="card-body">
    <form action="{{ route('siswa.store') }}" method="POST">@csrf
        <div class="form-row">
            <div class="form-group"><label class="form-label">NIS <span class="required">*</span></label><input type="text" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis') }}" required>@error('nis')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label class="form-label">NISN</label><input type="text" name="nisn" class="form-control @error('nisn') is-invalid @enderror" value="{{ old('nisn') }}">@error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="form-group"><label class="form-label">Nama Siswa <span class="required">*</span></label><input type="text" name="nama_siswa" class="form-control @error('nama_siswa') is-invalid @enderror" value="{{ old('nama_siswa') }}" required>@error('nama_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Jenis Kelamin <span class="required">*</span></label><select name="jenis_kelamin" class="form-control" required><option value="">-- Pilih --</option><option value="L" {{ old('jenis_kelamin')=='L'?'selected':'' }}>Laki-laki</option><option value="P" {{ old('jenis_kelamin')=='P'?'selected':'' }}>Perempuan</option></select></div>
            <div class="form-group"><label class="form-label">Status <span class="required">*</span></label><select name="status" class="form-control" required><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option><option value="lulus">Lulus</option></select></div>
        </div>
        <div class="form-group"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ old('alamat') }}</textarea></div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button><a href="{{ route('siswa.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

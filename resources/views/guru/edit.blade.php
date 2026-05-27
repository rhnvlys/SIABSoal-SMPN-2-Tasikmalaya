@extends('layouts.app')
@section('title', 'Edit Guru')
@section('page-title', 'Edit Guru')
@section('content')
<div class="page-header"><h1>Edit Guru</h1><p>{{ $guru->nama_guru }}</p></div>
<div class="card" style="max-width:680px"><div class="card-body">
    <form action="{{ route('guru.update', $guru) }}" method="POST">
        @csrf @method('PUT')
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Guru <span class="required">*</span></label>
                <input type="text" name="nama_guru" class="form-control @error('nama_guru') is-invalid @enderror" value="{{ old('nama_guru', $guru->nama_guru) }}" required>@error('nama_guru')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label class="form-label">NIP</label>
                <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" value="{{ old('nip', $guru->nip) }}">@error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control"><option value="">--</option><option value="L" {{ old('jenis_kelamin',$guru->jenis_kelamin)=='L'?'selected':'' }}>Laki-laki</option><option value="P" {{ old('jenis_kelamin',$guru->jenis_kelamin)=='P'?'selected':'' }}>Perempuan</option></select></div>
            <div class="form-group"><label class="form-label">No HP</label>
                <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $guru->no_hp) }}"></div>
        </div>
        <div class="form-group"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $guru->alamat) }}</textarea></div>
        <div class="form-group"><label class="form-label">Status <span class="required">*</span></label>
            <select name="status" class="form-control" required><option value="aktif" {{ old('status',$guru->status)=='aktif'?'selected':'' }}>Aktif</option><option value="nonaktif" {{ old('status',$guru->status)=='nonaktif'?'selected':'' }}>Nonaktif</option></select></div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Perbarui</button><a href="{{ route('guru.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

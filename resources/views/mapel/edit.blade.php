@extends('layouts.app')
@section('title', 'Edit Mapel')
@section('page-title', 'Edit Mapel')
@section('content')
<div class="page-header"><h1>Edit Mata Pelajaran</h1></div>
<div class="card" style="max-width:520px"><div class="card-body">
    <form action="{{ route('mapel.update', $mapel) }}" method="POST">@csrf @method('PUT')
        <div class="form-group"><label class="form-label">Kode Mapel <span class="required">*</span></label><input type="text" name="kode_mapel" class="form-control @error('kode_mapel') is-invalid @enderror" value="{{ old('kode_mapel', $mapel->kode_mapel) }}" required>@error('kode_mapel')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="form-group"><label class="form-label">Nama Mata Pelajaran <span class="required">*</span></label><input type="text" name="nama_mapel" class="form-control @error('nama_mapel') is-invalid @enderror" value="{{ old('nama_mapel', $mapel->nama_mapel) }}" required>@error('nama_mapel')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Perbarui</button><a href="{{ route('mapel.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

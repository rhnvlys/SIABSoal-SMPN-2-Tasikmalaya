@extends('layouts.app')
@section('title', 'Tambah Kelas')
@section('page-title', 'Tambah Kelas')
@section('content')
<div class="page-header"><h1>Tambah Kelas</h1></div>
<div class="card" style="max-width:680px"><div class="card-body">
    <form action="{{ route('kelas.store') }}" method="POST">@csrf
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Kelas <span class="required">*</span></label><input type="text" name="nama_kelas" class="form-control @error('nama_kelas') is-invalid @enderror" value="{{ old('nama_kelas') }}" placeholder="Contoh: IX A" required>@error('nama_kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label class="form-label">Tingkat <span class="required">*</span></label><select name="tingkat" class="form-control" required><option value="">-- Pilih --</option><option value="VII" {{ old('tingkat')=='VII'?'selected':'' }}>VII</option><option value="VIII" {{ old('tingkat')=='VIII'?'selected':'' }}>VIII</option><option value="IX" {{ old('tingkat')=='IX'?'selected':'' }}>IX</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Tahun Ajaran <span class="required">*</span></label><select name="tahun_ajaran_id" class="form-control" required><option value="">-- Pilih --</option>@foreach($tahunAjaranList as $ta)<option value="{{ $ta->id }}" {{ old('tahun_ajaran_id')==$ta->id?'selected':'' }}>{{ $ta->label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Wali Kelas</label><select name="wali_kelas_id" class="form-control"><option value="">-- Tidak Ada --</option>@foreach($guruList as $g)<option value="{{ $g->id }}" {{ old('wali_kelas_id')==$g->id?'selected':'' }}>{{ $g->nama_guru }}</option>@endforeach</select></div>
        </div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button><a href="{{ route('kelas.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

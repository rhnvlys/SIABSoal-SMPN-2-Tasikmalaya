@extends('layouts.app')
@section('title', 'Tambah Tahun Ajaran')
@section('page-title', 'Tambah Tahun Ajaran')
@section('content')
<div class="page-header"><h1>Tambah Tahun Ajaran</h1></div>
<div class="card" style="max-width:520px"><div class="card-body">
    <form action="{{ route('tahun-ajaran.store') }}" method="POST">@csrf
        <div class="form-group"><label class="form-label">Tahun Ajaran <span class="required">*</span></label><input type="text" name="tahun_ajaran" class="form-control" value="{{ old('tahun_ajaran') }}" placeholder="Contoh: 2025/2026" required></div>
        <div class="form-group"><label class="form-label">Semester <span class="required">*</span></label><select name="semester" class="form-control" required><option value="Ganjil" {{ old('semester')=='Ganjil'?'selected':'' }}>Ganjil</option><option value="Genap" {{ old('semester')=='Genap'?'selected':'' }}>Genap</option></select></div>
        <div class="form-group"><label class="form-label">Status <span class="required">*</span></label><select name="status" class="form-control" required><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
            <small class="text-muted">Jika status diset "Aktif", tahun ajaran lain akan otomatis dinonaktifkan.</small></div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button><a href="{{ route('tahun-ajaran.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

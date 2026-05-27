@extends('layouts.app')
@section('title', 'Edit Tahun Ajaran')
@section('page-title', 'Edit Tahun Ajaran')
@section('content')
<div class="page-header"><h1>Edit Tahun Ajaran</h1></div>
<div class="card" style="max-width:520px"><div class="card-body">
    <form action="{{ route('tahun-ajaran.update', $tahun_ajaran) }}" method="POST">@csrf @method('PUT')
        <div class="form-group"><label class="form-label">Tahun Ajaran <span class="required">*</span></label><input type="text" name="tahun_ajaran" class="form-control" value="{{ old('tahun_ajaran', $tahun_ajaran->tahun_ajaran) }}" required></div>
        <div class="form-group"><label class="form-label">Semester <span class="required">*</span></label><select name="semester" class="form-control" required><option value="Ganjil" {{ old('semester',$tahun_ajaran->semester)=='Ganjil'?'selected':'' }}>Ganjil</option><option value="Genap" {{ old('semester',$tahun_ajaran->semester)=='Genap'?'selected':'' }}>Genap</option></select></div>
        <div class="form-group"><label class="form-label">Status <span class="required">*</span></label><select name="status" class="form-control" required><option value="aktif" {{ old('status',$tahun_ajaran->status)=='aktif'?'selected':'' }}>Aktif</option><option value="nonaktif" {{ old('status',$tahun_ajaran->status)=='nonaktif'?'selected':'' }}>Nonaktif</option></select></div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Perbarui</button><a href="{{ route('tahun-ajaran.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

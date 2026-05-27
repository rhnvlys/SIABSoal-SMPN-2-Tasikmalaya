@extends('layouts.app')
@section('title', 'Edit Kelas')
@section('page-title', 'Edit Kelas')
@section('content')
<div class="page-header"><h1>Edit Kelas</h1><p>{{ $kelas->nama_kelas }}</p></div>
<div class="card" style="max-width:680px"><div class="card-body">
    <form action="{{ route('kelas.update', $kelas) }}" method="POST">@csrf @method('PUT')
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Kelas <span class="required">*</span></label><input type="text" name="nama_kelas" class="form-control" value="{{ old('nama_kelas', $kelas->nama_kelas) }}" required></div>
            <div class="form-group"><label class="form-label">Tingkat <span class="required">*</span></label><select name="tingkat" class="form-control" required><option value="VII" {{ old('tingkat',$kelas->tingkat)=='VII'?'selected':'' }}>VII</option><option value="VIII" {{ old('tingkat',$kelas->tingkat)=='VIII'?'selected':'' }}>VIII</option><option value="IX" {{ old('tingkat',$kelas->tingkat)=='IX'?'selected':'' }}>IX</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Tahun Ajaran <span class="required">*</span></label><select name="tahun_ajaran_id" class="form-control" required>@foreach($tahunAjaranList as $ta)<option value="{{ $ta->id }}" {{ old('tahun_ajaran_id',$kelas->tahun_ajaran_id)==$ta->id?'selected':'' }}>{{ $ta->label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Wali Kelas</label><select name="wali_kelas_id" class="form-control"><option value="">-- Tidak Ada --</option>@foreach($guruList as $g)<option value="{{ $g->id }}" {{ old('wali_kelas_id',$kelas->wali_kelas_id)==$g->id?'selected':'' }}>{{ $g->nama_guru }}</option>@endforeach</select></div>
        </div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Perbarui</button><a href="{{ route('kelas.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@endsection

@extends('layouts.app')
@section('title', 'Tambah Ujian')
@section('page-title', 'Tambah Ujian')
@section('content')
<div class="page-header"><h1>Tambah Ujian</h1></div>
<div class="card" style="max-width:720px"><div class="card-body">
    <form action="{{ route('ujian.store') }}" method="POST">@csrf
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Ujian <span class="required">*</span></label><input type="text" name="nama_ujian" class="form-control @error('nama_ujian') is-invalid @enderror" value="{{ old('nama_ujian') }}" required>@error('nama_ujian')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label class="form-label">Jenis Ujian <span class="required">*</span></label><select name="jenis_ujian" class="form-control" required><option value="">-- Pilih --</option>@foreach(['UH','STS','SAS','ASAJ','PAS','PAT','UTS','UAS','Lainnya'] as $j)<option value="{{ $j }}" {{ old('jenis_ujian')==$j?'selected':'' }}>{{ $j }}</option>@endforeach</select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Guru <span class="required">*</span></label><select name="guru_id" class="form-control" required><option value="">-- Pilih --</option>@foreach($guruList as $g)<option value="{{ $g->id }}" {{ old('guru_id')==$g->id?'selected':'' }}>{{ $g->nama_guru }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Mata Pelajaran <span class="required">*</span></label><select name="mapel_id" class="form-control" required><option value="">-- Pilih --</option>@foreach($mapelList as $m)<option value="{{ $m->id }}" {{ old('mapel_id')==$m->id?'selected':'' }}>{{ $m->nama_mapel }}</option>@endforeach</select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Tahun Ajaran <span class="required">*</span></label><select name="tahun_ajaran_id" class="form-control" required><option value="">-- Pilih --</option>@foreach($tahunAjaranList as $ta)<option value="{{ $ta->id }}" {{ old('tahun_ajaran_id')==$ta->id?'selected':'' }}>{{ $ta->label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Tanggal Ujian <span class="required">*</span></label><input type="date" name="tanggal_ujian" class="form-control" value="{{ old('tanggal_ujian') }}" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Jumlah Soal <span class="required">*</span></label><input type="number" name="jumlah_soal" class="form-control" value="{{ old('jumlah_soal', 20) }}" min="1" max="50" required></div>
            <div class="form-group"><label class="form-label">KKM <span class="required">*</span></label><input type="number" name="kkm" class="form-control" value="{{ old('kkm', 75) }}" min="0" max="100" step="0.01" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Metode Kelompok <span class="required">*</span></label><select name="metode_kelompok" class="form-control" id="metode-kelompok" required><option value="persen_50" {{ old('metode_kelompok')=='persen_50'?'selected':'' }}>50% Atas / 50% Bawah</option><option value="manual" {{ old('metode_kelompok')=='manual'?'selected':'' }}>Manual</option></select></div>
            <div class="form-group" id="manual-field" style="display:{{ old('metode_kelompok')=='manual'?'block':'none' }}"><label class="form-label">Jumlah Kelompok Manual</label><input type="number" name="jumlah_kelompok_manual" class="form-control" value="{{ old('jumlah_kelompok_manual') }}" min="1"></div>
        </div>
        <div class="form-group"><label class="form-label">Kelas <span class="required">*</span> <small class="text-muted">(centang kelas peserta ujian)</small></label>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;padding:12px;border:1px solid var(--border-color);border-radius:var(--radius-md)">
                @foreach($kelasList as $k)
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" name="kelas_ids[]" value="{{ $k->id }}" {{ in_array($k->id, old('kelas_ids', [])) ? 'checked' : '' }}> {{ $k->nama_kelas }} ({{ $k->tahunAjaran->tahun_ajaran ?? '' }})</label>
                @endforeach
            </div>
            @error('kelas_ids')<div class="invalid-feedback" style="display:block">{{ $message }}</div>@enderror
        </div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button><a href="{{ route('ujian.index') }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@push('scripts')
<script>document.getElementById('metode-kelompok').addEventListener('change',function(){document.getElementById('manual-field').style.display=this.value==='manual'?'block':'none'});</script>
@endpush
@endsection

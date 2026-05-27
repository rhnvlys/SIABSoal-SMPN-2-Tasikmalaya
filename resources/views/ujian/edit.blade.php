@extends('layouts.app')
@section('title', 'Edit Ujian')
@section('page-title', 'Edit Ujian')
@section('content')
<div class="page-header"><h1>Edit Ujian</h1><p>{{ $ujian->nama_ujian }}</p></div>
<div class="card" style="max-width:720px"><div class="card-body">
    <form action="{{ route('ujian.update', $ujian) }}" method="POST">@csrf @method('PUT')
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Ujian <span class="required">*</span></label><input type="text" name="nama_ujian" class="form-control" value="{{ old('nama_ujian', $ujian->nama_ujian) }}" required></div>
            <div class="form-group"><label class="form-label">Jenis Ujian <span class="required">*</span></label><select name="jenis_ujian" class="form-control" required>@foreach(['UH','STS','SAS','ASAJ','PAS','PAT','UTS','UAS','Lainnya'] as $j)<option value="{{ $j }}" {{ old('jenis_ujian',$ujian->jenis_ujian)==$j?'selected':'' }}>{{ $j }}</option>@endforeach</select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Guru <span class="required">*</span></label><select name="guru_id" class="form-control" required>@foreach($guruList as $g)<option value="{{ $g->id }}" {{ old('guru_id',$ujian->guru_id)==$g->id?'selected':'' }}>{{ $g->nama_guru }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Mata Pelajaran <span class="required">*</span></label><select name="mapel_id" class="form-control" required>@foreach($mapelList as $m)<option value="{{ $m->id }}" {{ old('mapel_id',$ujian->mapel_id)==$m->id?'selected':'' }}>{{ $m->nama_mapel }}</option>@endforeach</select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Tahun Ajaran <span class="required">*</span></label><select name="tahun_ajaran_id" class="form-control" required>@foreach($tahunAjaranList as $ta)<option value="{{ $ta->id }}" {{ old('tahun_ajaran_id',$ujian->tahun_ajaran_id)==$ta->id?'selected':'' }}>{{ $ta->label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Tanggal Ujian <span class="required">*</span></label><input type="date" name="tanggal_ujian" class="form-control" value="{{ old('tanggal_ujian', $ujian->tanggal_ujian?->format('Y-m-d')) }}" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Jumlah Soal</label><input type="number" class="form-control" value="{{ $ujian->jumlah_soal }}" disabled><small class="text-muted">Jumlah soal tidak dapat diubah setelah dibuat.</small></div>
            <div class="form-group"><label class="form-label">KKM <span class="required">*</span></label><input type="number" name="kkm" class="form-control" value="{{ old('kkm', $ujian->kkm) }}" min="0" max="100" step="0.01" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Metode Kelompok <span class="required">*</span></label><select name="metode_kelompok" class="form-control" id="metode-kelompok" required><option value="persen_50" {{ old('metode_kelompok',$ujian->metode_kelompok)=='persen_50'?'selected':'' }}>50% Atas / 50% Bawah</option><option value="manual" {{ old('metode_kelompok',$ujian->metode_kelompok)=='manual'?'selected':'' }}>Manual</option></select></div>
            <div class="form-group" id="manual-field" style="display:{{ old('metode_kelompok',$ujian->metode_kelompok)=='manual'?'block':'none' }}"><label class="form-label">Jumlah Kelompok Manual</label><input type="number" name="jumlah_kelompok_manual" class="form-control" value="{{ old('jumlah_kelompok_manual', $ujian->jumlah_kelompok_manual) }}" min="1"></div>
        </div>
        <div class="form-group"><label class="form-label">Kelas <span class="required">*</span></label>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;padding:12px;border:1px solid var(--border-color);border-radius:var(--radius-md)">
                @foreach($kelasList as $k)
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" name="kelas_ids[]" value="{{ $k->id }}" {{ in_array($k->id, old('kelas_ids', $selectedKelas)) ? 'checked' : '' }}> {{ $k->nama_kelas }} ({{ $k->tahunAjaran->tahun_ajaran ?? '' }})</label>
                @endforeach
            </div>
        </div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Perbarui</button><a href="{{ route('ujian.show', $ujian) }}" class="btn btn-outline">Batal</a></div>
    </form>
</div></div>
@push('scripts')
<script>document.getElementById('metode-kelompok').addEventListener('change',function(){document.getElementById('manual-field').style.display=this.value==='manual'?'block':'none'});</script>
@endpush
@endsection

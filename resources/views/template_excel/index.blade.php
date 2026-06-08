@extends('layouts.app')
@section('title', 'Template Excel')
@section('page-title', 'Template Excel')

@section('content')
<div class="page-header">
    <h1>Template Excel</h1>
    <p>Download format Excel yang familiar untuk administrasi penilaian, import jawaban, daftar nilai, dan rekap nilai.</p>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-file-earmark-spreadsheet"></i> Pilih Ujian untuk Template Import</div>
    <div class="card-body">
        <form action="{{ route('template-excel.index') }}" method="GET" class="d-flex gap-1 flex-wrap">
            <select name="ujian_id" class="form-control" style="max-width:420px">
                <option value="">-- Pilih ujian --</option>
                @foreach($ujianList as $ujian)
                    <option value="{{ $ujian->id }}" {{ $selectedUjian?->id === $ujian->id ? 'selected' : '' }}>
                        {{ $ujian->nama_ujian }} - {{ $ujian->mapel->nama_mapel ?? '-' }} - {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Gunakan</button>
            @if($selectedUjian)
                <a href="{{ route('ujian.show', $selectedUjian) }}" class="btn btn-outline"><i class="bi bi-eye"></i> Detail Ujian</a>
            @endif
        </form>

        @if($selectedUjian)
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:14px">
                <div><strong>Jenis Penilaian:</strong> {{ $selectedUjian->jenis_penilaian_label }}</div>
                <div><strong>KKTP/KKM:</strong> {{ $selectedUjian->kktp_value }}</div>
                <div><strong>Jumlah Soal:</strong> {{ $selectedUjian->jumlah_soal }}</div>
                <div><strong>Tahun Ajaran:</strong> {{ $selectedUjian->tahunAjaran->label ?? '-' }}</div>
            </div>
        @else
            <p class="text-muted" style="margin-top:12px">Template kunci jawaban, jawaban A/B/C/D/E, dan skor 0/1 membutuhkan pilihan ujian agar jumlah kolom soal sesuai.</p>
        @endif
    </div>
</div>

<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
    @foreach($templates as $template)
        @php
            $disabled = $template['requires_ujian'] && !$selectedUjian;
            $downloadUrl = route('template-excel.download', array_filter([
                'type' => $template['type'],
                'ujian_id' => $template['requires_ujian'] ? $selectedUjian?->id : null,
            ]));
        @endphp
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-center gap-2 mb-2">
                    <span class="sidebar-brand-mark" style="width:38px;height:38px"><i class="bi {{ $template['icon'] }}"></i></span>
                    <div>
                        <h3 style="font-size:var(--font-size-base);margin:0">{{ $template['title'] }}</h3>
                        <small class="text-muted">PETUNJUK, DATA_INPUT, CONTOH</small>
                    </div>
                </div>
                <p class="text-muted" style="min-height:54px">{{ $template['description'] }}</p>
                @if($disabled)
                    <button type="button" class="btn btn-outline" disabled><i class="bi bi-lock"></i> Pilih Ujian</button>
                @else
                    <a href="{{ $downloadUrl }}" class="btn btn-primary" data-loading data-loading-text="Mengunduh...">
                        <i class="bi bi-download"></i> Download
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection

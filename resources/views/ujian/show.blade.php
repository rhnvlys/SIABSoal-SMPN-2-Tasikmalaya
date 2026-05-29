@extends('layouts.app')
@section('title', 'Detail Ujian')
@section('page-title', 'Detail Ujian')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>{{ $ujian->nama_ujian }}</h1><p>{{ $ujian->mapel->nama_mapel ?? '' }} — {{ $ujian->guru->nama_guru ?? '' }}</p></div>
    @include('components.badge', ['type' => $ujian->status])
</div>

{{-- Info Cards --}}
<div class="stats-grid mb-3">
    @include('components.card-stat', ['value' => $ujian->jumlah_soal, 'label' => 'Jumlah Soal', 'icon' => 'bi-file-earmark-text-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $ujian->pesertaUjian->count(), 'label' => 'Peserta', 'icon' => 'bi-people-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $ujian->kkm, 'label' => 'KKM', 'icon' => 'bi-bullseye', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $ujian->analisisButir->count(), 'label' => 'Soal Dianalisis', 'icon' => 'bi-clipboard-data-fill', 'color' => 'purple'])
</div>

{{-- Info Detail --}}
<div class="card mb-3"><div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
        <div><strong>Jenis:</strong> {{ $ujian->jenis_ujian }}</div>
        <div><strong>Tanggal:</strong> {{ $ujian->tanggal_ujian?->format('d/m/Y') ?? '-' }}</div>
        <div><strong>Tahun Ajaran:</strong> {{ $ujian->tahunAjaran->label ?? '-' }}</div>
        <div><strong>Kelas:</strong> {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</div>
        <div><strong>Metode Kelompok:</strong> {{ $ujian->metode_kelompok === 'persen_50' ? '50% Atas/Bawah' : 'Manual ('.$ujian->jumlah_kelompok_manual.')' }}</div>
    </div>
</div></div>

{{-- Action Buttons (Workflow) --}}
<div class="card mb-3"><div class="card-header">Alur Kerja</div><div class="card-body">
    <div class="btn-group flex-wrap">
        @if(!auth()->user()->isKepalaSekolah())
        <a href="{{ route('kunci-jawaban.index', $ujian) }}" class="btn {{ $ujian->status === 'draft' ? 'btn-primary' : 'btn-outline' }}"><i class="bi bi-key-fill"></i> Kunci Jawaban</a>
        <a href="{{ route('data-mentah.index', $ujian) }}" class="btn {{ $ujian->status === 'kunci_lengkap' ? 'btn-primary' : 'btn-outline' }}"><i class="bi bi-table"></i> T1 Data Mentah</a>
        <a href="{{ route('olah-data.index', $ujian) }}" class="btn {{ $ujian->status === 'data_mentah' ? 'btn-primary' : 'btn-outline' }}"><i class="bi bi-bar-chart-line-fill"></i> T2 Olah Data</a>
        @endif
        <a href="{{ route('analisis-data.index', $ujian) }}" class="btn {{ $ujian->status === 'olah_data' ? 'btn-primary' : 'btn-outline' }}"><i class="bi bi-clipboard-data-fill"></i> T3 Analisis</a>
        <a href="{{ route('daftar-nilai.index', $ujian) }}" class="btn btn-outline"><i class="bi bi-journal-text"></i> T4 Daftar Nilai</a>
        <a href="{{ route('rekap-nilai.index', $ujian) }}" class="btn btn-outline"><i class="bi bi-file-earmark-bar-graph-fill"></i> T5 Rekap Nilai</a>
    </div>
</div></div>

{{-- Kunci Jawaban Preview --}}
<div class="card mb-3"><div class="card-header d-flex justify-between align-center"><span>Kunci Jawaban</span>
    @if(!auth()->user()->isKepalaSekolah())
    <a href="{{ route('kunci-jawaban.index', $ujian) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i> Edit</a>
    @endif
</div>
<div class="card-body" style="display:flex;flex-wrap:wrap;gap:6px">
    @foreach($ujian->soal as $soal)
    <span class="badge {{ $soal->kunci_jawaban ? 'badge-aktif' : 'badge-draft' }}">{{ $soal->nomor_soal }}. {{ $soal->kunci_jawaban ?? '-' }}</span>
    @endforeach
</div></div>

<div class="btn-group mt-2">
    @if(!auth()->user()->isKepalaSekolah())
    <a href="{{ route('ujian.edit', $ujian) }}" class="btn btn-outline"><i class="bi bi-pencil"></i> Edit Ujian</a>
    @endif
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
@endsection

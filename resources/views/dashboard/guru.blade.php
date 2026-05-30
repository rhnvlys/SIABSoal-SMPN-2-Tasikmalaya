@extends('layouts.app')

@section('title', 'Dashboard Guru')
@section('page-title', 'Dashboard Guru')

@section('content')
<div class="page-header">
    <h1>Dashboard Guru</h1>
    <p>Ringkasan ujian, kelas wali, dan laporan yang menjadi tanggung jawab Anda.</p>
</div>

<div class="stats-grid">
    @include('components.card-stat', ['value' => $stats['ujian'], 'label' => 'Ujian Saya', 'icon' => 'bi-file-earmark-text-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $stats['kelas_wali'], 'label' => 'Kelas Wali', 'icon' => 'bi-building', 'color' => 'purple'])
    @include('components.card-stat', ['value' => $stats['siswa_wali'], 'label' => 'Siswa Kelas Saya', 'icon' => 'bi-mortarboard-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $stats['t1'], 'label' => 'Sudah T1', 'icon' => 'bi-table', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $stats['t2'], 'label' => 'Sudah T2', 'icon' => 'bi-bar-chart-line-fill', 'color' => 'teal'])
    @include('components.card-stat', ['value' => $stats['t3'], 'label' => 'Sudah T3', 'icon' => 'bi-clipboard-data-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $analisis->soal_baik ?? 0, 'label' => 'Soal Baik', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $analisis->soal_revisi ?? 0, 'label' => 'Soal Revisi', 'icon' => 'bi-pencil-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $analisis->soal_buang ?? 0, 'label' => 'Soal Buang', 'icon' => 'bi-trash-fill', 'color' => 'red'])
</div>

<div class="btn-group mb-3">
    <a href="{{ route('ujian.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Ujian</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-key-fill"></i> Input Kunci Jawaban</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-table"></i> Data Mentah T1</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-bar-chart-line-fill"></i> Olah Data T2</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-clipboard-data-fill"></i> Analisis Data T3</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-journal-text"></i> Daftar Nilai T4</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-file-earmark-bar-graph-fill"></i> Rekap Nilai T5</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-download"></i> Laporan Export</a>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-building"></i> Daftar Kelas Wali</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Kelas</th><th>Tahun Ajaran</th><th>Jumlah Siswa</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($kelasWali as $kelas)
                    <tr>
                        <td class="fw-semibold">{{ $kelas->nama_kelas }}</td>
                        <td>{{ $kelas->tahunAjaran->label ?? '-' }}</td>
                        <td>{{ $kelas->siswa_kelas_count }}</td>
                        <td><a href="{{ route('kelas.show', $kelas) }}" class="btn btn-sm btn-outline"><i class="bi bi-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted" style="padding:24px">Belum ada kelas wali.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Ujian Terbaru Milik Saya</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nama Ujian</th><th>Mata Pelajaran</th><th>Kelas</th><th>Status</th><th>Tanggal</th></tr></thead>
            <tbody>
                @forelse ($ujianTerbaru as $u)
                    <tr>
                        <td><a href="{{ route('ujian.show', $u) }}" class="fw-semibold">{{ $u->nama_ujian }}</a></td>
                        <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $u->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
                        <td>@include('components.badge', ['type' => $u->status])</td>
                        <td>{{ $u->tanggal_ujian?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding:24px">Belum ada ujian milik Anda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history"></i> Aktivitas Saya Terbaru</div>
    @include('components.activity-table', ['logs' => $aktivitasSaya, 'showUser' => false])
</div>
@endsection

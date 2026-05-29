@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="page-header">
    <h1>Dashboard Admin</h1>
    <p>Ringkasan seluruh data SIABSoal SMPN 2 Tasikmalaya.</p>
</div>

<div class="stats-grid">
    @include('components.card-stat', ['value' => $stats['guru'], 'label' => 'Jumlah Guru', 'icon' => 'bi-person-badge-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $stats['siswa'], 'label' => 'Jumlah Siswa', 'icon' => 'bi-mortarboard-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $stats['kelas'], 'label' => 'Jumlah Kelas', 'icon' => 'bi-building', 'color' => 'purple'])
    @include('components.card-stat', ['value' => $stats['mapel'], 'label' => 'Mata Pelajaran', 'icon' => 'bi-book-fill', 'color' => 'teal'])
    @include('components.card-stat', ['value' => $stats['ujian'], 'label' => 'Jumlah Ujian', 'icon' => 'bi-file-earmark-text-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $analisis->total ?? 0, 'label' => 'Soal Dianalisis', 'icon' => 'bi-clipboard-data-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $analisis->soal_baik ?? 0, 'label' => 'Soal Baik', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $analisis->soal_revisi ?? 0, 'label' => 'Soal Revisi', 'icon' => 'bi-pencil-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $analisis->soal_buang ?? 0, 'label' => 'Soal Buang', 'icon' => 'bi-trash-fill', 'color' => 'red'])
    @include('components.card-stat', ['value' => $analisis->soal_mudah ?? 0, 'label' => 'Soal Mudah', 'icon' => 'bi-speedometer', 'color' => 'green'])
    @include('components.card-stat', ['value' => $analisis->soal_sedang ?? 0, 'label' => 'Soal Sedang', 'icon' => 'bi-speedometer2', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $analisis->soal_sukar ?? 0, 'label' => 'Soal Sukar', 'icon' => 'bi-speedometer', 'color' => 'red'])
</div>

@php
    $statusLabels = [
        'draft' => 'Draft',
        'kunci_lengkap' => 'Kunci Lengkap',
        'data_mentah' => 'Data Mentah T1',
        'olah_data' => 'Olah Data T2',
        'dianalisis' => 'Dianalisis T3',
        'selesai' => 'Selesai',
    ];
@endphp
<div style="margin-bottom:24px">
    <h3 style="font-size:var(--font-size-base);font-weight:600;margin-bottom:12px;color:var(--text-secondary)">
        <i class="bi bi-diagram-3-fill"></i> Status Progres Ujian
    </h3>
    <div class="status-grid">
        @foreach($statusLabels as $statusKey => $statusLabel)
            <div class="status-card">
                <div class="status-count">{{ $statusProgres[$statusKey] ?? 0 }}</div>
                <div class="status-label">{{ $statusLabel }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="btn-group mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-primary"><i class="bi bi-people-fill"></i> Manajemen User</a>
    <a href="{{ route('guru.index') }}" class="btn btn-outline"><i class="bi bi-person-badge-fill"></i> Data Guru</a>
    <a href="{{ route('siswa.index') }}" class="btn btn-outline"><i class="bi bi-mortarboard-fill"></i> Data Siswa</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-file-earmark-text-fill"></i> Data Ujian</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-download"></i> Laporan Export</a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Ujian Terbaru Seluruh Sekolah</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nama Ujian</th><th>Mata Pelajaran</th><th>Guru</th><th>Kelas</th><th>Status</th><th>Tanggal</th></tr></thead>
            <tbody>
                @forelse ($ujianTerbaru as $u)
                    <tr>
                        <td><a href="{{ route('ujian.show', $u) }}" class="fw-semibold">{{ $u->nama_ujian }}</a></td>
                        <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $u->guru->nama_guru ?? '-' }}</td>
                        <td>{{ $u->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
                        <td>@include('components.badge', ['type' => $u->status])</td>
                        <td>{{ $u->tanggal_ujian?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted" style="padding:24px">Belum ada data ujian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

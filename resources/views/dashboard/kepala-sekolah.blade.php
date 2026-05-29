@extends('layouts.app')

@section('title', 'Dashboard Kepala Sekolah')
@section('page-title', 'Dashboard Kepala Sekolah')

@section('content')
<div class="page-header">
    <h1>Dashboard Kepala Sekolah</h1>
    <p>Monitoring ringkasan laporan ujian dan kualitas soal seluruh sekolah.</p>
</div>

<div class="stats-grid">
    @include('components.card-stat', ['value' => $stats['guru'], 'label' => 'Jumlah Guru', 'icon' => 'bi-person-badge-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $stats['siswa'], 'label' => 'Jumlah Siswa', 'icon' => 'bi-mortarboard-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $stats['kelas'], 'label' => 'Jumlah Kelas', 'icon' => 'bi-building', 'color' => 'purple'])
    @include('components.card-stat', ['value' => $stats['mapel'], 'label' => 'Mata Pelajaran', 'icon' => 'bi-book-fill', 'color' => 'teal'])
    @include('components.card-stat', ['value' => $stats['ujian_selesai'], 'label' => 'Ujian Selesai', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $stats['ujian_belum_selesai'], 'label' => 'Ujian Belum Selesai', 'icon' => 'bi-hourglass-split', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $analisis->soal_baik ?? 0, 'label' => 'Soal Baik', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $analisis->soal_revisi ?? 0, 'label' => 'Soal Revisi', 'icon' => 'bi-pencil-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $analisis->soal_buang ?? 0, 'label' => 'Soal Buang', 'icon' => 'bi-trash-fill', 'color' => 'red'])
    @include('components.card-stat', ['value' => $analisis->soal_mudah ?? 0, 'label' => 'Soal Mudah', 'icon' => 'bi-speedometer', 'color' => 'green'])
    @include('components.card-stat', ['value' => $analisis->soal_sedang ?? 0, 'label' => 'Soal Sedang', 'icon' => 'bi-speedometer2', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $analisis->soal_sukar ?? 0, 'label' => 'Soal Sukar', 'icon' => 'bi-speedometer', 'color' => 'red'])
</div>

<div class="btn-group mb-3">
    <a href="{{ route('ujian.index') }}" class="btn btn-primary"><i class="bi bi-clipboard-data-fill"></i> Lihat Analisis Data T3</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-journal-text"></i> Daftar Nilai T4</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-file-earmark-bar-graph-fill"></i> Rekap Nilai T5</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-download"></i> Export Laporan</a>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-bar-chart-fill"></i> Rata-rata Nilai Per Ujian Terbaru</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nama Ujian</th><th>Mapel</th><th>Kelas</th><th>Rata-rata Nilai</th></tr></thead>
            <tbody>
                @forelse ($rataNilaiUjian as $u)
                    <tr>
                        <td><a href="{{ route('ujian.show', $u) }}" class="fw-semibold">{{ $u->nama_ujian }}</a></td>
                        <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $u->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
                        <td>{{ number_format((float) $u->rata_rata_nilai, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted" style="padding:24px">Belum ada nilai ujian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-file-earmark-bar-graph-fill"></i> Daftar Laporan Terbaru</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nama Ujian</th><th>Guru</th><th>Mapel</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($laporanTerbaru as $u)
                    <tr>
                        <td class="fw-semibold">{{ $u->nama_ujian }}</td>
                        <td>{{ $u->guru->nama_guru ?? '-' }}</td>
                        <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
                        <td>@include('components.badge', ['type' => $u->status])</td>
                        <td><a href="{{ route('analisis-data.index', $u) }}" class="btn btn-sm btn-outline"><i class="bi bi-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding:24px">Belum ada laporan terbaru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Ujian Terbaru Seluruh Sekolah</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nama Ujian</th><th>Mata Pelajaran</th><th>Guru</th><th>Kelas</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($ujianTerbaru as $u)
                    <tr>
                        <td><a href="{{ route('ujian.show', $u) }}" class="fw-semibold">{{ $u->nama_ujian }}</a></td>
                        <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $u->guru->nama_guru ?? '-' }}</td>
                        <td>{{ $u->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
                        <td>@include('components.badge', ['type' => $u->status])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding:24px">Belum ada data ujian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

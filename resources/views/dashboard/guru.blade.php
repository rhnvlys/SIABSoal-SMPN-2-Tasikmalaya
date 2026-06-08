@extends('layouts.app')

@section('title', 'Dashboard Guru')
@section('page-title', 'Dashboard Guru')

@section('content')
<div class="page-header">
    <h1>Dashboard Guru</h1>
    <p>Ringkasan pekerjaan penilaian, template Excel, dan laporan ujian Anda.</p>
</div>

<div class="stats-grid">
    @include('components.card-stat', ['value' => $stats['ujian_saya'], 'label' => 'Jumlah Ujian Saya', 'icon' => 'bi-file-earmark-text-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $stats['kelas_saya'], 'label' => 'Kelas Saya', 'icon' => 'bi-building', 'color' => 'purple'])
    @include('components.card-stat', ['value' => $stats['siswa_kelas_saya'], 'label' => 'Siswa Kelas Saya', 'icon' => 'bi-mortarboard-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $stats['belum_lengkap'], 'label' => 'Penilaian Belum Lengkap', 'icon' => 'bi-exclamation-circle-fill', 'color' => 'red'])
    @include('components.card-stat', ['value' => $stats['sudah_dianalisis'], 'label' => 'Penilaian Sudah Dianalisis', 'icon' => 'bi-clipboard-data-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $stats['laporan_tersedia'], 'label' => 'Export/Laporan Tersedia', 'icon' => 'bi-download', 'color' => 'teal'])
</div>

<div class="btn-group mb-3">
    <a href="{{ route('ujian.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Buat Ujian</a>
    <a href="{{ route('template-excel.index', $ujianLanjutkan ? ['ujian_id' => $ujianLanjutkan->id] : []) }}" class="btn btn-outline"><i class="bi bi-file-earmark-spreadsheet"></i> Template Excel</a>
    <a href="{{ route('ujian.index') }}" class="btn btn-outline"><i class="bi bi-list-check"></i> Data Ujian Saya</a>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-between align-center flex-wrap gap-2">
        <span><i class="bi bi-signpost-2-fill"></i> Alur Cepat Pengolahan Nilai</span>
        @if($ujianLanjutkan)
            <a href="{{ route('ujian.show', $ujianLanjutkan) }}" class="btn btn-sm btn-outline">{{ $ujianLanjutkan->nama_ujian }}</a>
        @endif
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px">
            @foreach($quickFlow as $index => $step)
                @php
                    $statusClass = $step['done'] ? 'badge-green' : 'badge-amber';
                    $statusText = $step['done'] ? 'Selesai' : 'Belum';
                    $url = $step['enabled'] ? route($step['route'], $step['params']) : route('ujian.index');
                @endphp
                <div style="border:1px solid var(--border-color);border-radius:var(--radius-md);padding:12px;background:var(--bg-card);display:flex;gap:10px;align-items:flex-start">
                    <div style="width:30px;height:30px;border-radius:50%;background:var(--bg-secondary);display:flex;align-items:center;justify-content:center;font-weight:700">{{ $index + 1 }}</div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600">{{ $step['label'] }}</div>
                        <span class="badge {{ $statusClass }}" style="margin:6px 0">{{ $statusText }}</span>
                        <div>
                            <a href="{{ $url }}" class="btn btn-sm {{ $step['done'] ? 'btn-outline' : 'btn-primary' }}" style="margin-top:4px">
                                <i class="bi bi-arrow-right-circle"></i> Buka
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-clock-history"></i> Ujian Saya Terbaru</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Ujian</th>
                    <th>Jenis Penilaian</th>
                    <th>Mata Pelajaran</th>
                    <th>Kelas</th>
                    <th>Status Proses</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ujianTerbaru as $u)
                    <tr>
                        <td><a href="{{ route('ujian.show', $u) }}" class="fw-semibold">{{ $u->nama_ujian }}</a></td>
                        <td>{{ $u->jenis_penilaian_label }}</td>
                        <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $u->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
                        <td>@include('components.badge', ['type' => $u->status])</td>
                        <td>{{ $u->tanggal_ujian?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <a href="{{ route($u->next_action['route'], $u->next_action['params']) }}" class="btn btn-sm btn-primary">
                                {{ $u->next_action['label'] }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Belum ada ujian milik Anda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
    <div class="card-header"><i class="bi bi-clock-history"></i> Aktivitas Saya Terbaru</div>
    @include('components.activity-table', ['logs' => $aktivitasSaya, 'showUser' => false])
</div>
@endsection

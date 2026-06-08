@extends('layouts.app')

@section('title', 'Olah Data T2')
@section('page-title', 'Olah Data T2')

@section('content')
{{-- Breadcrumb --}}
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Data Ujian', 'route' => 'ujian.index'],
    ['label' => $ujian->nama_ujian, 'route' => 'ujian.show', 'params' => $ujian->id],
    ['label' => 'Olah Data T2'],
]])

<div class="page-header">
    <h1>Olah Data T2</h1>
    <p>{{ $ujian->nama_ujian }}</p>
</div>

{{-- Box Instruksi --}}
<div class="alert alert-info" style="margin-bottom:24px">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Tahap 2</strong> - Tahap ini digunakan untuk mengurutkan nilai siswa dan membentuk kelompok atas serta kelompok bawah.
</div>

{{-- Informasi Ujian --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> Informasi Ujian
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Nama Ujian:</strong> {{ $ujian->nama_ujian }}</div>
            <div><strong>Jenis Penilaian:</strong> {{ $ujian->jenis_penilaian_label }}</div>
            <div><strong>Mata Pelajaran:</strong> {{ $ujian->mapel->nama_mapel ?? '-' }}</div>
            <div><strong>Jumlah Soal:</strong> {{ $ujian->jumlah_soal }}</div>
            <div><strong>KKTP/KKM:</strong> {{ $ujian->kktp_value }}</div>
            <div><strong>Tujuan Pembelajaran:</strong> {{ $ujian->tujuan_pembelajaran ?: '-' }}</div>
            <div><strong>Lingkup Materi:</strong> {{ $ujian->lingkup_materi ?: '-' }}</div>
            <div><strong>Status:</strong> @include('components.badge', ['type' => $ujian->status])</div>
            <div><strong>Metode Kelompok:</strong> {{ $ujian->metode_kelompok === 'persen_50' ? 'Otomatis 50%' : 'Manual' }}</div>
        </div>
    </div>
</div>

{{-- Statistik Siswa --}}
@php
    $totalPeserta = $pesertaHadir->count() + $pesertaTidakHadir->count();
@endphp
<div class="stats-grid" style="margin-bottom:24px">
    @include('components.card-stat', ['value' => $totalPeserta, 'label' => 'Jumlah Siswa', 'icon' => 'bi-people-fill', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $pesertaHadir->count(), 'label' => 'Siswa Hadir', 'icon' => 'bi-person-check-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $pesertaTidakHadir->count(), 'label' => 'Tidak Hadir', 'icon' => 'bi-person-x-fill', 'color' => 'red'])
    @include('components.card-stat', ['value' => $kelAtas->count(), 'label' => 'Kelompok Atas', 'icon' => 'bi-arrow-up-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $kelBawah->count(), 'label' => 'Kelompok Bawah', 'icon' => 'bi-arrow-down-circle-fill', 'color' => 'amber'])
    @include('components.card-stat', ['value' => $kelTengah->count(), 'label' => 'Kelompok Tengah', 'icon' => 'bi-dash-circle-fill', 'color' => 'purple'])
</div>

{{-- Tombol Proses --}}
@if(in_array($ujian->status, ['data_mentah', 'olah_data', 'dianalisis', 'selesai']))
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-sliders"></i> Metode Pembagian Kelompok
    </div>
    <div class="card-body">
        <form action="{{ route('olah-data.proses', $ujian) }}" method="POST"
              data-loading data-loading-text="Memproses..."
              data-confirm="Proses Olah Data T2 akan menghitung ulang ranking serta kelompok atas, bawah, dan tengah. Lanjutkan?"
              data-confirm-button="Ya, proses">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Metode</label>
                    <select name="metode_kelompok" id="metode_kelompok" class="form-control">
                        <option value="persen_50" {{ $ujian->metode_kelompok === 'persen_50' ? 'selected' : '' }}>Otomatis 50%</option>
                        <option value="manual" {{ $ujian->metode_kelompok === 'manual' ? 'selected' : '' }}>Manual jumlah kelompok</option>
                    </select>
                </div>
                <div class="form-group" id="jumlah_manual_group">
                    <label class="form-label">Jumlah Kelompok Atas/Bawah</label>
                    <input type="number" name="jumlah_kelompok_manual" class="form-control" min="1"
                           value="{{ old('jumlah_kelompok_manual', $ujian->jumlah_kelompok_manual) }}"
                           placeholder="Contoh: 10">
                    <small class="text-muted">Jumlah manual dikali 2 tidak boleh melebihi siswa hadir.</small>
                </div>
            </div>

            <div class="btn-group" style="margin-top:12px;flex-wrap:wrap;gap:8px">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-play-fill"></i> Proses Olah Data T2
                </button>

                @if($pesertaHadir->count() > 0)
                    <a href="{{ route('export.olah-data.excel', $ujian) }}" class="btn btn-outline" data-loading data-loading-text="Mengexport...">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                    </a>
                    <a href="{{ route('export.olah-data.pdf', $ujian) }}" class="btn btn-outline" data-loading data-loading-text="Mengexport...">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
@else
<div class="alert alert-warning" style="margin-bottom:24px">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Data Mentah (T1) belum diproses. Silakan proses Data Mentah terlebih dahulu.
</div>
@endif

{{-- Tabel Hasil Ranking --}}
@if($pesertaHadir->count() > 0)
<div class="card">
    <div class="card-header">
        <i class="bi bi-sort-numeric-down"></i> Hasil Ranking & Kelompok
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ranking</th>
                    <th>NIS</th>
                    <th>NISN</th>
                    <th>Nama Siswa</th>
                    <th>L/P</th>
                    <th class="text-center">Benar</th>
                    <th class="text-center">Salah</th>
                    <th class="text-center">Nilai</th>
                    <th>Kelompok</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pesertaHadir as $p)
                <tr>
                    <td style="font-weight:600">{{ $p->ranking ?? '-' }}</td>
                    <td>{{ $p->siswa->nis ?? '-' }}</td>
                    <td>{{ $p->siswa->nisn ?? '-' }}</td>
                    <td style="font-weight:600">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                    <td>{{ $p->siswa->jenis_kelamin ?? '-' }}</td>
                    <td class="text-center">{{ $p->jumlah_benar }}</td>
                    <td class="text-center">{{ $p->jumlah_salah }}</td>
                    <td class="text-center" style="font-weight:600">{{ number_format($p->nilai, 2) }}</td>
                    <td>
                        @if($p->kelompok === 'atas')
                            <span class="badge badge-green">Atas</span>
                        @elseif($p->kelompok === 'bawah')
                            <span class="badge badge-amber">Bawah</span>
                        @elseif($p->kelompok === 'tengah')
                            <span class="badge badge-purple">Tengah</span>
                        @else
                            <span class="badge badge-gray">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Siswa Tidak Hadir --}}
@if($pesertaTidakHadir->count() > 0)
<div class="card" style="margin-top:24px">
    <div class="card-header">
        <i class="bi bi-person-x-fill"></i> Siswa Tidak Hadir ({{ $pesertaTidakHadir->count() }} siswa)
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>L/P</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pesertaTidakHadir as $idx => $p)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $p->siswa->nis ?? '-' }}</td>
                    <td>{{ $p->siswa->nama_siswa ?? '-' }}</td>
                    <td>{{ $p->siswa->jenis_kelamin ?? '-' }}</td>
                    <td><span class="badge badge-red">Tidak Hadir</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@else
    @include('components.empty-state', [
        'icon' => 'bi-bar-chart-line',
        'title' => 'Belum ada data ranking',
        'description' => 'Klik tombol "Proses Olah Data T2" untuk memulai pengelompokan siswa.'
    ])
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var metode = document.getElementById('metode_kelompok');
    var manualGroup = document.getElementById('jumlah_manual_group');

    function toggleManualGroup() {
        if (!metode || !manualGroup) return;
        manualGroup.style.display = metode.value === 'manual' ? 'block' : 'none';
    }

    toggleManualGroup();
    if (metode) {
        metode.addEventListener('change', toggleManualGroup);
    }
});
</script>
@endpush

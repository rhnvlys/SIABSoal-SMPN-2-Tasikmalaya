@extends('layouts.app')

@section('title', 'Analisis Data T3')
@section('page-title', 'Analisis Data T3')

@section('content')
{{-- Breadcrumb --}}
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Data Ujian', 'route' => 'ujian.index'],
    ['label' => $ujian->nama_ujian, 'route' => 'ujian.show', 'params' => $ujian->id],
    ['label' => 'Analisis Data T3'],
]])

<div class="page-header">
    <h1>Analisis Data T3</h1>
    <p>{{ $ujian->nama_ujian }}</p>
</div>

{{-- Box Instruksi --}}
<div class="alert alert-info" style="margin-bottom:24px">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Tahap 3</strong> — Tahap ini digunakan untuk menghitung Daya Pembeda (DP) dan Tingkat Kesukaran (TK) setiap soal berdasarkan kelompok atas dan kelompok bawah.
</div>

{{-- Informasi Ujian --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> Informasi Ujian
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Nama Ujian:</strong> {{ $ujian->nama_ujian }}</div>
            <div><strong>Mata Pelajaran:</strong> {{ $ujian->mapel->nama_mapel ?? '-' }}</div>
            <div><strong>Guru:</strong> {{ $ujian->guru->nama_guru ?? '-' }}</div>
            <div><strong>Jumlah Soal:</strong> {{ $ujian->jumlah_soal }}</div>
        </div>
    </div>
</div>

{{-- Tombol Proses --}}
@if(in_array($ujian->status, ['olah_data', 'dianalisis', 'selesai']))
<div style="margin-bottom:24px">
    <form action="{{ route('analisis-data.proses', $ujian) }}" method="POST" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-primary" onclick="return confirm('Proses Analisis Data T3? Hasil analisis sebelumnya akan diperbarui.')">
            <i class="bi bi-calculator"></i> Proses Analisis Data T3
        </button>
    </form>

    @if($analisis->count() > 0)
        <a href="{{ route('export.analisis.excel', $ujian) }}" class="btn btn-outline">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
        </a>
        <a href="{{ route('export.analisis.pdf', $ujian) }}" class="btn btn-outline">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    @endif
</div>
@else
<div class="alert alert-warning" style="margin-bottom:24px">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Olah Data (T2) belum diproses. Silakan proses Olah Data terlebih dahulu sebelum melakukan analisis.
</div>
@endif

{{-- Hasil Analisis --}}
@if($analisis->count() > 0)
    {{-- Ringkasan --}}
    <div class="stats-grid" style="margin-bottom:24px">
        @include('components.card-stat', ['value' => $ringkasan['total'], 'label' => 'Soal Dianalisis', 'icon' => 'bi-clipboard-data-fill', 'color' => 'blue'])
        @include('components.card-stat', ['value' => $ringkasan['soal_baik'], 'label' => 'Soal Baik (DP)', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
        @include('components.card-stat', ['value' => $ringkasan['soal_revisi'], 'label' => 'Soal Revisi', 'icon' => 'bi-pencil-fill', 'color' => 'amber'])
        @include('components.card-stat', ['value' => $ringkasan['soal_buang'], 'label' => 'Soal Buang', 'icon' => 'bi-trash-fill', 'color' => 'red'])
        @include('components.card-stat', ['value' => $ringkasan['soal_mudah'], 'label' => 'Soal Mudah (TK)', 'icon' => 'bi-emoji-smile-fill', 'color' => 'green'])
        @include('components.card-stat', ['value' => $ringkasan['soal_sedang'], 'label' => 'Soal Sedang (TK)', 'icon' => 'bi-emoji-neutral-fill', 'color' => 'blue'])
        @include('components.card-stat', ['value' => $ringkasan['soal_sukar'], 'label' => 'Soal Sukar (TK)', 'icon' => 'bi-emoji-frown-fill', 'color' => 'red'])
    </div>

    {{-- Tabel Hasil Analisis --}}
    <div class="card">
        <div class="card-header">
            <i class="bi bi-table"></i> Hasil Analisis Data T3
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No Soal</th>
                        <th>BA</th>
                        <th>BB</th>
                        <th>JA</th>
                        <th>JB</th>
                        <th>DP</th>
                        <th>Kategori DP</th>
                        <th>TK</th>
                        <th>Kategori TK</th>
                        <th>Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analisis as $a)
                    <tr>
                        <td style="font-weight:600">{{ $a->nomor_soal }}</td>
                        <td>{{ $a->ba }}</td>
                        <td>{{ $a->bb }}</td>
                        <td>{{ $a->ja }}</td>
                        <td>{{ $a->jb }}</td>
                        <td>{{ number_format($a->dp, 3) }}</td>
                        <td>@include('components.badge', ['type' => 'dp_' . strtolower($a->kategori_dp)])</td>
                        <td>{{ number_format($a->tk, 3) }}</td>
                        <td>@include('components.badge', ['type' => 'tk_' . strtolower($a->kategori_tk)])</td>
                        <td>@include('components.badge', ['type' => 'keputusan_' . strtolower(str_replace(' ', '_', $a->keputusan))])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    @include('components.empty-state', [
        'icon' => 'bi-clipboard-data',
        'title' => 'Belum ada hasil analisis',
        'description' => 'Klik tombol "Proses Analisis Data T3" untuk menghitung DP dan TK.'
    ])
@endif
@endsection

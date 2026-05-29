@extends('layouts.app')

@section('title', 'Rekap Nilai T5')
@section('page-title', 'Rekap Nilai T5')

@section('content')
{{-- Breadcrumb --}}
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Data Ujian', 'route' => 'ujian.index'],
    ['label' => $ujian->nama_ujian, 'route' => 'ujian.show', 'params' => $ujian->id],
    ['label' => 'Rekap Nilai T5'],
]])

<div class="page-header">
    <h1>Rekap Nilai T5</h1>
    <p>{{ $ujian->nama_ujian }}</p>
</div>

{{-- Box Instruksi --}}
<div class="alert alert-info" style="margin-bottom:24px">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Tahap 5</strong> — Tahap ini menampilkan rekap nilai dan klasifikasi hasil ujian berdasarkan tabel sekolah.
</div>

{{-- Tombol Export --}}
<div class="btn-group mb-3">
    <a href="{{ route('export.rekap-nilai.excel', $ujian) }}" class="btn btn-outline" data-loading data-loading-text="Mengexport...">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
    </a>
    <a href="{{ route('export.rekap-nilai.pdf', $ujian) }}" class="btn btn-outline" data-loading data-loading-text="Mengexport...">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </a>
    <button onclick="window.print()" class="btn btn-outline">
        <i class="bi bi-printer"></i> Cetak Laporan
    </button>
</div>

{{-- A. Identitas Ujian --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> A. Identitas Ujian
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Nama Ujian:</strong> {{ $ujian->nama_ujian }}</div>
            <div><strong>Jenis Ujian:</strong> {{ $ujian->jenis_ujian }}</div>
            <div><strong>Mata Pelajaran:</strong> {{ $ujian->mapel->nama_mapel ?? '-' }}</div>
            <div><strong>Kelas:</strong> {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</div>
            <div><strong>Semester:</strong> {{ $ujian->tahunAjaran->semester ?? '-' }}</div>
            <div><strong>Tahun Pelajaran:</strong> {{ $ujian->tahunAjaran->tahun_ajaran ?? '-' }}</div>
            <div><strong>Guru Mata Pelajaran:</strong> {{ $ujian->guru->nama_guru ?? '-' }}</div>
            <div><strong>KKTP/KKM:</strong> {{ $ujian->kkm }}</div>
        </div>
    </div>
</div>

{{-- B. Kehadiran Siswa --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-people-fill"></i> B. Kehadiran Siswa
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Jumlah Siswa:</strong> {{ $ringkasan['jumlah_siswa'] }}</div>
            <div><strong>Siswa Hadir:</strong> <span class="badge badge-green">{{ $ringkasan['hadir'] }}</span></div>
            <div><strong>Siswa Tidak Hadir:</strong> <span class="badge badge-red">{{ $ringkasan['tidak_hadir'] }}</span></div>
        </div>
    </div>
</div>

{{-- C. Rentang Nilai --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-bar-chart-steps"></i> C. Rentang Nilai
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Nilai &lt; KKTP/KKM ({{ $ujian->kkm }}):</strong> <span class="badge badge-red">{{ $rentang_nilai['bawah_kkm'] }}</span></div>
            <div><strong>Nilai = KKTP/KKM ({{ $ujian->kkm }}):</strong> <span class="badge badge-amber">{{ $rentang_nilai['sama_kkm'] }}</span></div>
            <div><strong>Nilai &gt; KKTP/KKM ({{ $ujian->kkm }}):</strong> <span class="badge badge-green">{{ $rentang_nilai['atas_kkm'] }}</span></div>
        </div>
    </div>
</div>

{{-- D. Statistik Nilai --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-graph-up"></i> D. Statistik Nilai
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Nilai Tertinggi:</strong> {{ number_format($ringkasan['nilai_tertinggi'], 2) }}</div>
            <div><strong>Nilai Terendah:</strong> {{ number_format($ringkasan['nilai_terendah'], 2) }}</div>
            <div><strong>Rata-rata Nilai:</strong> {{ number_format($ringkasan['rata_rata'], 2) }}</div>
        </div>
    </div>
</div>

{{-- E. Ketuntasan --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-check-circle"></i> E. Ketuntasan
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Tuntas:</strong> <span class="badge badge-green">{{ $ketuntasan['tuntas'] }}</span></div>
            <div><strong>Belum Tuntas:</strong> <span class="badge badge-red">{{ $ketuntasan['belum_tuntas'] }}</span></div>
        </div>
    </div>
</div>

{{-- F. Ringkasan Analisis Soal --}}
@if($analisis_ringkasan['total'] > 0)
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <i class="bi bi-clipboard-data-fill"></i> F. Ringkasan Analisis Soal
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Jumlah Soal Dianalisis:</strong> {{ $analisis_ringkasan['total'] }}</div>
            <div><strong>Soal Baik (DP):</strong> <span class="badge badge-green">{{ $analisis_ringkasan['soal_baik'] }}</span></div>
            <div><strong>Soal Revisi:</strong> <span class="badge badge-amber">{{ $analisis_ringkasan['soal_revisi'] }}</span></div>
            <div><strong>Soal Buang:</strong> <span class="badge badge-red">{{ $analisis_ringkasan['soal_buang'] }}</span></div>
            <div><strong>Soal Mudah (TK):</strong> <span class="badge badge-green">{{ $analisis_ringkasan['soal_mudah'] }}</span></div>
            <div><strong>Soal Sedang (TK):</strong> <span class="badge badge-blue">{{ $analisis_ringkasan['soal_sedang'] }}</span></div>
            <div><strong>Soal Sukar (TK):</strong> <span class="badge badge-red">{{ $analisis_ringkasan['soal_sukar'] }}</span></div>
        </div>
    </div>
</div>
@endif

{{-- G. Area Tanda Tangan --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;padding:20px 40px">
            <div style="text-align:center;min-width:200px">
                <p style="margin-bottom:80px">Mengetahui,<br><strong>Kepala Sekolah</strong></p>
                <p style="border-top:1px solid var(--text-primary);padding-top:4px;font-weight:600">
                    {{ $sekolah['kepala_sekolah'] ?? '...........................' }}
                </p>
                <p style="font-size:13px">NIP. {{ $sekolah['nip_kepala_sekolah'] ?? '...........................' }}</p>
            </div>
            <div style="text-align:center;min-width:200px">
                <p style="margin-bottom:80px">Tasikmalaya, {{ now()->format('d F Y') }}<br><strong>Guru Mata Pelajaran</strong></p>
                <p style="border-top:1px solid var(--text-primary);padding-top:4px;font-weight:600">
                    {{ $ujian->guru->nama_guru ?? '...........................' }}
                </p>
                <p style="font-size:13px">NIP. {{ $ujian->guru->nip ?? '...........................' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

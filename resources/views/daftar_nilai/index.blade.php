@extends('layouts.app')

@section('title', 'Daftar Nilai T4')
@section('page-title', 'Daftar Nilai T4')

@section('content')
{{-- Breadcrumb --}}
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Data Ujian', 'route' => 'ujian.index'],
    ['label' => $ujian->nama_ujian, 'route' => 'ujian.show', 'params' => $ujian->id],
    ['label' => 'Daftar Nilai T4'],
]])

<div class="page-header">
    <h1>Daftar Nilai T4</h1>
    <p>{{ $ujian->nama_ujian }}</p>
</div>

{{-- Box Instruksi --}}
<div class="alert alert-info" style="margin-bottom:24px">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Tahap 4</strong> — Tahap ini menampilkan daftar nilai siswa berdasarkan hasil pengolahan jawaban pada Tahap 1.
</div>

{{-- Header Laporan --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-body" style="text-align:center;padding:24px">
        <p style="margin:0;font-weight:600;text-transform:uppercase">PEMERINTAH KOTA TASIKMALAYA</p>
        <p style="margin:0;font-weight:600;text-transform:uppercase">DINAS PENDIDIKAN</p>
        <p style="margin:0;font-weight:700;font-size:18px;text-transform:uppercase">SMP NEGERI 2 TASIKMALAYA</p>
        <hr style="margin:12px auto;width:60%;border-color:var(--border-color)">
        <p style="margin:0;font-weight:700;font-size:20px;text-transform:uppercase">DAFTAR NILAI</p>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Nama Ujian:</strong> {{ $ujian->nama_ujian }}</div>
            <div><strong>Jenis Penilaian:</strong> {{ $ujian->jenis_penilaian_label }}</div>
            <div><strong>Mata Pelajaran:</strong> {{ $ujian->mapel->nama_mapel ?? '-' }}</div>
            <div><strong>Kelas:</strong> {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</div>
            <div><strong>Semester:</strong> {{ $ujian->tahunAjaran->semester ?? '-' }}</div>
            <div><strong>Tahun Ajaran:</strong> {{ $ujian->tahunAjaran->tahun_ajaran ?? '-' }}</div>
            <div><strong>Nama Guru:</strong> {{ $ujian->guru->nama_guru ?? '-' }}</div>
            <div><strong>KKTP/KKM:</strong> {{ $ujian->kktp_value }}</div>
            <div><strong>Tujuan Pembelajaran:</strong> {{ $ujian->tujuan_pembelajaran ?: '-' }}</div>
            <div><strong>Lingkup Materi:</strong> {{ $ujian->lingkup_materi ?: '-' }}</div>
        </div>
    </div>
</div>

{{-- Tombol Export --}}
<div class="btn-group mb-3">
    @if($peserta->count() > 0 && !auth()->user()->isKepalaSekolah())
    <button type="button" class="btn btn-primary" onclick="document.getElementById('manualNilaiModal').classList.add('show')">
        <i class="bi bi-pencil-square"></i> Input Manual Nilai
    </button>
    @endif
    @php
        $cleanUjianName = str_replace(' ', '_', strtolower($ujian->nama_ujian));
    @endphp
    <a href="{{ route('export.daftar-nilai.excel', ['ujian' => $ujian, 'filename' => 'daftar_nilai_' . $cleanUjianName . '.xlsx']) }}" class="btn btn-outline">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
    </a>
    <a href="{{ route('export.daftar-nilai.pdf', ['ujian' => $ujian, 'filename' => 'daftar_nilai_' . $cleanUjianName . '.pdf']) }}" class="btn btn-outline">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </a>
    <button onclick="window.print()" class="btn btn-outline">
        <i class="bi bi-printer"></i> Cetak Laporan
    </button>
</div>

{{-- Tabel Daftar Nilai --}}
@if($peserta->count() > 0)
<div class="card">
    <div class="card-header">
        <i class="bi bi-journal-text"></i> Daftar Nilai Peserta Didik
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>NISN</th>
                    <th>Nama Siswa</th>
                    <th>L/P</th>
                    <th>Status Kehadiran</th>
                    <th class="text-center">Jumlah Benar</th>
                    <th class="text-center">Jumlah Salah</th>
                    <th class="text-center">Nilai</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($peserta as $idx => $p)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $p->siswa->nis ?? '-' }}</td>
                    <td>{{ $p->siswa->nisn ?? '-' }}</td>
                    <td style="font-weight:600">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                    <td>{{ $p->siswa->jenis_kelamin ?? '-' }}</td>
                    <td>
                        @if($p->status_kehadiran === 'hadir')
                            <span class="badge badge-green">Hadir</span>
                        @else
                            <span class="badge badge-red">Tidak Hadir</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $p->jumlah_benar }}</td>
                    <td class="text-center">{{ $p->jumlah_salah }}</td>
                    <td class="text-center" style="font-weight:600">{{ number_format($p->nilai, 2) }}</td>
                    <td>
                        @if($p->keterangan === 'tercapai')
                            <span class="badge badge-green">TERCAPAI</span>
                        @elseif($p->keterangan === 'perlu_peningkatan')
                            <span class="badge badge-amber">PERLU PENINGKATAN</span>
                        @elseif($p->keterangan === 'tidak_hadir')
                            <span class="badge badge-red">TIDAK HADIR</span>
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

{{-- Ringkasan --}}
<div class="card" style="margin-top:24px">
    <div class="card-header">
        <i class="bi bi-graph-up"></i> Ringkasan Nilai
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div><strong>Jumlah Siswa:</strong> {{ $ringkasan['jumlah_siswa'] }}</div>
            <div><strong>Jumlah Hadir:</strong> {{ $ringkasan['hadir'] }}</div>
            <div><strong>Jumlah Tidak Hadir:</strong> {{ $ringkasan['tidak_hadir'] }}</div>
            <div><strong>Nilai Tertinggi:</strong> {{ number_format($ringkasan['nilai_tertinggi'], 2) }}</div>
            <div><strong>Nilai Terendah:</strong> {{ number_format($ringkasan['nilai_terendah'], 2) }}</div>
            <div><strong>Rata-rata Nilai:</strong> {{ number_format($ringkasan['rata_rata'], 2) }}</div>
            <div><strong>Jumlah Tercapai:</strong> <span class="badge badge-green">{{ $ringkasan['jumlah_tercapai'] }}</span></div>
            <div><strong>Perlu Peningkatan:</strong> <span class="badge badge-amber">{{ $ringkasan['jumlah_perlu_peningkatan'] }}</span></div>
        </div>
    </div>
</div>
@else
    @include('components.empty-state', [
        'icon' => 'bi-journal-text',
        'title' => 'Belum ada data nilai',
        'description' => 'Proses Data Mentah T1 terlebih dahulu untuk menampilkan daftar nilai.'
    ])
@endif

@if($peserta->count() > 0 && !auth()->user()->isKepalaSekolah())
<div class="modal-overlay" id="manualNilaiModal">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h3>Input Manual Daftar Nilai T4</h3>
            <button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form action="{{ route('daftar-nilai.manual', $ujian) }}" method="POST" data-loading data-loading-text="Memproses...">
            @csrf
            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom:16px">
                    Nilai manual digunakan jika daftar nilai tidak berasal dari hasil olah jawaban T1.
                </div>
                <div class="table-responsive" style="max-height:60vh">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama Peserta Didik</th>
                                <th>Status Kehadiran</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($peserta as $p)
                            <tr>
                                <td>{{ $p->siswa->nis ?? '-' }}</td>
                                <td style="font-weight:600">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                                <td>
                                    <select name="status_kehadiran[{{ $p->id }}]" class="form-control" style="min-width:150px">
                                        <option value="hadir" {{ $p->status_kehadiran === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                        <option value="tidak_hadir" {{ $p->status_kehadiran === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="nilai[{{ $p->id }}]" class="form-control" min="0" max="100" step="0.01" value="{{ old('nilai.' . $p->id, $p->nilai) }}" style="width:120px">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Nilai Manual</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

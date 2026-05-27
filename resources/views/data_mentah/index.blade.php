@extends('layouts.app')

@section('title', 'Data Mentah T1')
@section('page-title', 'Data Mentah T1')

@section('content')
{{-- Breadcrumb --}}
@include('components.breadcrumb', ['items' => [
    ['label' => 'Dashboard', 'route' => 'dashboard'],
    ['label' => 'Data Ujian', 'route' => 'ujian.index'],
    ['label' => $ujian->nama_ujian, 'route' => 'ujian.show', 'params' => $ujian->id],
    ['label' => 'Data Mentah T1'],
]])

<div class="page-header">
    <h1>Data Mentah T1</h1>
    <p>{{ $ujian->nama_ujian }}</p>
</div>

{{-- Box Instruksi --}}
<div class="alert alert-info" style="margin-bottom:24px">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Tahap 1</strong> — Tahap ini digunakan untuk memasukkan jawaban siswa. Sistem akan mengubah jawaban menjadi 1 jika benar dan 0 jika salah berdasarkan kunci jawaban.
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
            <div><strong>Kelas:</strong> {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</div>
            <div><strong>Guru:</strong> {{ $ujian->guru->nama_guru ?? '-' }}</div>
            <div><strong>Tahun Ajaran:</strong> {{ $ujian->tahunAjaran->tahun_ajaran ?? '-' }} — Semester {{ $ujian->tahunAjaran->semester ?? '-' }}</div>
            <div><strong>Jumlah Soal:</strong> {{ $ujian->jumlah_soal }}</div>
            <div><strong>KKTP/KKM:</strong> {{ $ujian->kkm }}</div>
            <div><strong>Status:</strong> @include('components.badge', ['type' => $ujian->status])</div>
        </div>
    </div>
</div>

{{-- Validasi: Kunci Jawaban harus lengkap --}}
@php
    $kunciLengkap = $ujian->isKunciLengkap();
    $soalList = $ujian->soal->sortBy('nomor_soal');
@endphp

@if(!$kunciLengkap)
<div class="alert alert-warning" style="margin-bottom:24px">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu sebelum memproses Data Mentah T1.
    <a href="{{ route('kunci-jawaban.index', $ujian) }}" class="btn btn-sm btn-primary" style="margin-left:8px">
        <i class="bi bi-key-fill"></i> Lengkapi Kunci Jawaban
    </a>
</div>
@endif

{{-- Tombol Aksi --}}
<div class="btn-group mb-3" style="flex-wrap:wrap;gap:8px">
    <a href="{{ route('ujian.index') }}" class="btn btn-outline">
        <i class="bi bi-search"></i> Pilih Ujian
    </a>

    @if($peserta->count() > 0)
    <button type="button" class="btn btn-outline" onclick="document.getElementById('manualAbcdModal').classList.add('show')" @if(!$kunciLengkap) disabled @endif>
        <i class="bi bi-pencil-square"></i> Input Manual Jawaban
    </button>

    <button type="button" class="btn btn-outline" onclick="document.getElementById('manualSkorModal').classList.add('show')" @if(!$kunciLengkap) disabled @endif>
        <i class="bi bi-123"></i> Input Manual Skor 0/1
    </button>
    @endif

    {{-- Import Jawaban A/B/C/D/E --}}
    <button type="button" class="btn btn-primary" onclick="document.getElementById('importAbcdModal').classList.add('show')" @if(!$kunciLengkap) disabled @endif>
        <i class="bi bi-upload"></i> Import Jawaban A/B/C/D/E
    </button>

    {{-- Import Skor 0/1 --}}
    <button type="button" class="btn btn-primary" onclick="document.getElementById('importSkorModal').classList.add('show')" @if(!$kunciLengkap) disabled @endif>
        <i class="bi bi-upload"></i> Import Skor 0/1
    </button>

    {{-- Download Template --}}
    <a href="{{ route('data-mentah.template', [$ujian, 'abcd']) }}" class="btn btn-outline">
        <i class="bi bi-download"></i> Download Template Jawaban
    </a>
    <a href="{{ route('data-mentah.template', [$ujian, 'biner']) }}" class="btn btn-outline">
        <i class="bi bi-download"></i> Download Template Skor
    </a>

    {{-- Proses Data Mentah --}}
    @if($peserta->count() > 0 && $kunciLengkap)
    <form action="{{ route('data-mentah.proses', $ujian) }}" method="POST" style="display:inline-flex;gap:8px;align-items:center">
        @csrf
        <select name="mode" class="form-control" style="width:180px">
            <option value="abcd">Mode Jawaban A-E</option>
            <option value="biner">Mode Skor 0/1</option>
        </select>
        <button type="submit" class="btn btn-success" onclick="return confirm('Proses Data Mentah T1? Jawaban akan dikonversi ke skor 0/1.')">
            <i class="bi bi-play-fill"></i> Proses Data Mentah T1
        </button>
    </form>
    @endif

    {{-- Export --}}
    @if($peserta->count() > 0)
    <a href="{{ route('export.data-mentah.excel', $ujian) }}" class="btn btn-outline">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
    </a>
    @endif
</div>

{{-- Tabel Data Mentah --}}
@if($peserta->count() > 0)
<div class="card">
    <div class="card-header">
        <i class="bi bi-table"></i> Data Mentah — {{ $peserta->count() }} Siswa
    </div>
    <div class="table-responsive table-sticky-cols">
        <table class="table table-data-mentah">
            <thead>
                <tr>
                    <th class="sticky-col sticky-col-1">No</th>
                    <th class="sticky-col sticky-col-2">NIS</th>
                    <th class="sticky-col sticky-col-3">NISN</th>
                    <th class="sticky-col sticky-col-4">Nama Siswa</th>
                    <th>L/P</th>
                    <th>Kehadiran</th>
                    @foreach($soalList as $soal)
                    <th class="text-center soal-col">Soal {{ $soal->nomor_soal }}</th>
                    @endforeach
                    <th class="text-center">Benar</th>
                    <th class="text-center">Salah</th>
                    <th class="text-center">Nilai</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($peserta as $idx => $p)
                @php
                    $jawabanMap = $p->jawabanSiswa->keyBy('soal_id');
                    $keterangan = match($p->keterangan) {
                        'tercapai' => 'TERCAPAI',
                        'perlu_peningkatan' => 'PERLU PENINGKATAN',
                        'tidak_hadir' => 'TIDAK HADIR',
                        default => '-',
                    };
                @endphp
                <tr>
                    <td class="sticky-col sticky-col-1">{{ $idx + 1 }}</td>
                    <td class="sticky-col sticky-col-2" style="font-weight:600">{{ $p->siswa->nis ?? '-' }}</td>
                    <td class="sticky-col sticky-col-3">{{ $p->siswa->nisn ?? '-' }}</td>
                    <td class="sticky-col sticky-col-4" style="font-weight:600">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                    <td>{{ $p->siswa->jenis_kelamin ?? '-' }}</td>
                    <td>
                        @if($p->status_kehadiran === 'hadir')
                            <span class="badge badge-green">Hadir</span>
                        @else
                            <span class="badge badge-red">Tidak Hadir</span>
                        @endif
                    </td>
                    @foreach($soalList as $soal)
                    @php
                        $js = $jawabanMap->get($soal->id);
                        $skor = $js ? $js->skor_biner : 0;
                    @endphp
                    <td class="text-center soal-col {{ $skor === 1 ? 'skor-benar' : 'skor-salah' }}">
                        {{ $p->status_kehadiran === 'hadir' ? $skor : '-' }}
                    </td>
                    @endforeach
                    <td class="text-center" style="font-weight:600">{{ $p->jumlah_benar }}</td>
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
@else
    @include('components.empty-state', [
        'icon' => 'bi-table',
        'title' => 'Belum ada data peserta ujian',
        'description' => 'Import data jawaban siswa menggunakan tombol di atas untuk memulai.'
    ])
@endif

{{-- Modal Input Manual Jawaban A/B/C/D/E --}}
@if($peserta->count() > 0)
<div class="modal-overlay" id="manualAbcdModal">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h3>Input Manual Jawaban A/B/C/D/E</h3>
            <button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form action="{{ route('data-mentah.manual', $ujian) }}" method="POST" data-loading>
            @csrf
            <input type="hidden" name="mode" value="abcd">
            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom:16px">
                    Masukkan jawaban A/B/C/D/E. Jawaban kosong akan dihitung salah saat diproses.
                </div>
                <div class="table-responsive" style="max-height:60vh">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Status</th>
                                @foreach($soalList as $soal)
                                    <th class="text-center">Soal {{ $soal->nomor_soal }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($peserta as $p)
                                @php $jawabanMap = $p->jawabanSiswa->keyBy('soal_id'); @endphp
                                <tr>
                                    <td>{{ $p->siswa->nis ?? '-' }}</td>
                                    <td style="font-weight:600">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                                    <td>
                                        <select name="status_kehadiran[{{ $p->id }}]" class="form-control" style="min-width:140px">
                                            <option value="hadir" {{ $p->status_kehadiran === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                            <option value="tidak_hadir" {{ $p->status_kehadiran === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                                        </select>
                                    </td>
                                    @foreach($soalList as $soal)
                                        @php $current = $jawabanMap->get($soal->id)?->jawaban; @endphp
                                        <td>
                                            <select name="jawaban[{{ $p->id }}][{{ $soal->id }}]" class="form-control" style="width:72px">
                                                <option value="">-</option>
                                                @foreach(['A','B','C','D','E'] as $opsi)
                                                    <option value="{{ $opsi }}" {{ $current === $opsi ? 'selected' : '' }}>{{ $opsi }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Input Manual</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Input Manual Skor 0/1 --}}
<div class="modal-overlay" id="manualSkorModal">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h3>Input Manual Skor 0/1</h3>
            <button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form action="{{ route('data-mentah.manual', $ujian) }}" method="POST" data-loading>
            @csrf
            <input type="hidden" name="mode" value="biner">
            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom:16px">
                    Masukkan skor 1 untuk benar dan 0 untuk salah. Skor kosong otomatis dianggap 0.
                </div>
                <div class="table-responsive" style="max-height:60vh">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Status</th>
                                @foreach($soalList as $soal)
                                    <th class="text-center">Skor {{ $soal->nomor_soal }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($peserta as $p)
                                @php $jawabanMap = $p->jawabanSiswa->keyBy('soal_id'); @endphp
                                <tr>
                                    <td>{{ $p->siswa->nis ?? '-' }}</td>
                                    <td style="font-weight:600">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                                    <td>
                                        <select name="status_kehadiran[{{ $p->id }}]" class="form-control" style="min-width:140px">
                                            <option value="hadir" {{ $p->status_kehadiran === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                            <option value="tidak_hadir" {{ $p->status_kehadiran === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                                        </select>
                                    </td>
                                    @foreach($soalList as $soal)
                                        @php $current = (string) ($jawabanMap->get($soal->id)?->skor_biner ?? 0); @endphp
                                        <td>
                                            <select name="skor[{{ $p->id }}][{{ $soal->id }}]" class="form-control" style="width:72px">
                                                <option value="0" {{ $current === '0' ? 'selected' : '' }}>0</option>
                                                <option value="1" {{ $current === '1' ? 'selected' : '' }}>1</option>
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Input Manual</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal Import Jawaban A/B/C/D/E --}}
<div class="modal-overlay" id="importAbcdModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Jawaban A/B/C/D/E</h3>
            <button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form action="{{ route('data-mentah.preview', $ujian) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="mode" value="abcd">
            <div class="modal-body">
                <p>Upload file Excel/CSV dengan format jawaban A/B/C/D/E sesuai template.</p>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" required class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Preview Import</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Import Skor 0/1 --}}
<div class="modal-overlay" id="importSkorModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Skor 0/1</h3>
            <button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form action="{{ route('data-mentah.preview', $ujian) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="mode" value="biner">
            <div class="modal-body">
                <p>Upload file Excel/CSV dengan format skor 0/1 sesuai template.</p>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" required class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Preview Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Sticky columns for Data Mentah table */
.table-sticky-cols { position: relative; }
.table-data-mentah .sticky-col {
    position: sticky;
    background: var(--bg-card);
    z-index: 2;
}
.table-data-mentah thead .sticky-col {
    z-index: 3;
    background: var(--bg-sidebar);
}
.sticky-col-1 { left: 0; min-width: 40px; }
.sticky-col-2 { left: 40px; min-width: 80px; }
.sticky-col-3 { left: 120px; min-width: 120px; }
.sticky-col-4 { left: 240px; min-width: 180px; box-shadow: 2px 0 4px rgba(0,0,0,0.06); }

.soal-col { min-width: 64px; font-size: 15px; }
.skor-benar { color: var(--success); font-weight: 600; }
.skor-salah { color: var(--text-muted); }

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.show { display: flex; }
.modal-content {
    background: var(--bg-card);
    border-radius: var(--radius);
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    max-height: 92vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.modal-content-wide { max-width: min(1200px, 96vw); }
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
}
.modal-header h3 { margin: 0; font-size: 18px; }
.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-secondary);
}
.modal-body { padding: 20px; overflow:auto; }
.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>
@endpush

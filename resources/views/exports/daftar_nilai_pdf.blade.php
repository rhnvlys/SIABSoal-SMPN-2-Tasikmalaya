<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Nilai — {{ $ujian->nama_ujian }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #1F2937; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h3 { margin: 0; font-size: 11pt; }
        .header h2 { margin: 4px 0; font-size: 14pt; }
        .header p { margin: 2px 0; font-size: 9pt; color: #4B5563; }
        .header hr { border: 1px solid #1F2937; margin: 8px 0; }
        .school-logo { width: 56px; height: 56px; object-fit: contain; margin-bottom: 4px; }
        .info-table { width: 100%; margin-bottom: 16px; border: none; }
        .info-table td { padding: 2px 8px; font-size: 9pt; vertical-align: top; }
        .info-table td:first-child { font-weight: bold; width: 200px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 9pt; }
        table.data th, table.data td { border: 1px solid #9CA3AF; padding: 4px 8px; }
        table.data th { background: #E5E7EB; font-weight: bold; text-align: center; font-size: 8pt; }
        table.data td { text-align: center; }
        table.data td:nth-child(4) { text-align: left; }
        .ringkasan { margin-bottom: 20px; }
        .ringkasan h4 { font-size: 10pt; margin-bottom: 8px; }
        .ringkasan td { padding: 2px 12px 2px 0; font-size: 9pt; }
        .ttd { margin-top: 40px; width: 100%; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; padding: 0 40px; }
        .ttd .nama { border-top: 1px solid #1F2937; padding-top: 4px; font-weight: bold; }
        .footer { font-size: 8pt; color: #6B7280; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        @if($sekolah && $sekolah->logoPath())
            <img src="{{ $sekolah->logoPath() }}" class="school-logo" alt="Logo sekolah">
        @endif
        <h3>PEMERINTAH KOTA TASIKMALAYA</h3>
        <h3>DINAS PENDIDIKAN</h3>
        <h2>SMP NEGERI 2 TASIKMALAYA</h2>
        <hr>
        <h2>DAFTAR NILAI</h2>
    </div>

    {{-- Info Ujian --}}
    <table class="info-table">
        <tr><td>Nama Ujian</td><td>: {{ $ujian->nama_ujian }}</td></tr>
        <tr><td>Mata Pelajaran</td><td>: {{ $ujian->mapel->nama_mapel ?? '-' }}</td></tr>
        <tr><td>Kelas</td><td>: {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td></tr>
        <tr><td>Semester</td><td>: {{ $ujian->tahunAjaran->semester ?? '-' }}</td></tr>
        <tr><td>Tahun Pelajaran</td><td>: {{ $ujian->tahunAjaran->tahun_ajaran ?? '-' }}</td></tr>
        <tr><td>Guru Mata Pelajaran</td><td>: {{ $ujian->guru->nama_guru ?? '-' }}</td></tr>
        <tr><td>KKTP/KKM</td><td>: {{ $ujian->kkm }}</td></tr>
    </table>

    {{-- Tabel --}}
    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama Peserta Didik</th>
                <th>L/P</th>
                <th>Kehadiran</th>
                <th>Skor PG</th>
                <th>Salah</th>
                <th>Nilai</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($peserta as $idx => $p)
            @php
                $keterangan = match($p->keterangan) {
                    'tercapai' => 'TERCAPAI',
                    'perlu_peningkatan' => 'PERLU PENINGKATAN',
                    'tidak_hadir' => 'TIDAK HADIR',
                    default => '-',
                };
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $p->siswa->nis ?? '-' }}</td>
                <td>{{ $p->siswa->nisn ?? '-' }}</td>
                <td style="text-align:left">{{ $p->siswa->nama_siswa ?? '-' }}</td>
                <td>{{ $p->siswa->jenis_kelamin ?? '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $p->status_kehadiran)) }}</td>
                <td>{{ $p->jumlah_benar }}</td>
                <td>{{ $p->jumlah_salah }}</td>
                <td style="font-weight:bold">{{ number_format($p->nilai, 2) }}</td>
                <td>{{ $keterangan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Ringkasan --}}
    <div class="ringkasan">
        <h4>Ringkasan</h4>
        <table>
            <tr><td>Jumlah Siswa</td><td>: {{ $ringkasan['jumlah_siswa'] }}</td></tr>
            <tr><td>Hadir</td><td>: {{ $ringkasan['hadir'] }}</td></tr>
            <tr><td>Tidak Hadir</td><td>: {{ $ringkasan['tidak_hadir'] }}</td></tr>
            <tr><td>Nilai Tertinggi</td><td>: {{ number_format($ringkasan['nilai_tertinggi'], 2) }}</td></tr>
            <tr><td>Nilai Terendah</td><td>: {{ number_format($ringkasan['nilai_terendah'], 2) }}</td></tr>
            <tr><td>Rata-rata</td><td>: {{ number_format($ringkasan['rata_rata'], 2) }}</td></tr>
            <tr><td>Tercapai</td><td>: {{ $ringkasan['jumlah_tercapai'] }}</td></tr>
            <tr><td>Perlu Peningkatan</td><td>: {{ $ringkasan['jumlah_perlu_peningkatan'] }}</td></tr>
        </table>
    </div>

    {{-- Tanda Tangan --}}
    <table class="ttd">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p><strong>Kepala Sekolah</strong></p>
                <br><br><br><br>
                <p class="nama">{{ $sekolah['kepala_sekolah'] ?? '...........................' }}</p>
                <p style="font-size:8pt">NIP. {{ $sekolah['nip_kepala_sekolah'] ?? '...........................' }}</p>
            </td>
            <td>
                <p>Tasikmalaya, {{ now()->format('d F Y') }}</p>
                <p><strong>Guru Mata Pelajaran</strong></p>
                <br><br><br><br>
                <p class="nama">{{ $ujian->guru->nama_guru ?? '...........................' }}</p>
                <p style="font-size:8pt">NIP. {{ $ujian->guru->nip ?? '...........................' }}</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Dicetak oleh SIABSoal SMPN 2 Tasikmalaya — {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>

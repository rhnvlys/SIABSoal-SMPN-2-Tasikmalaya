<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai — {{ $ujian->nama_ujian }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #1F2937; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h3 { margin: 0; font-size: 11pt; }
        .header h2 { margin: 4px 0; font-size: 14pt; }
        .header p { margin: 2px 0; font-size: 9pt; color: #4B5563; }
        .header hr { border: 1px solid #1F2937; margin: 8px 0; }
        .section { margin-bottom: 16px; }
        .section h4 { font-size: 10pt; margin-bottom: 8px; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px; }
        .section table { border: none; }
        .section td { padding: 2px 12px 2px 0; font-size: 9pt; vertical-align: top; }
        .section td:first-child { font-weight: bold; width: 200px; }
        .ttd { margin-top: 40px; width: 100%; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; padding: 0 40px; }
        .ttd .nama { border-top: 1px solid #1F2937; padding-top: 4px; font-weight: bold; }
        .footer { font-size: 8pt; color: #6B7280; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h3>PEMERINTAH KOTA TASIKMALAYA</h3>
        <h3>DINAS PENDIDIKAN</h3>
        <h2>SMP NEGERI 2 TASIKMALAYA</h2>
        <hr>
        <h2>REKAP NILAI</h2>
        <p>SIABSoal SMPN 2 Tasikmalaya</p>
    </div>

    {{-- A. Identitas Ujian --}}
    <div class="section">
        <h4>A. Identitas Ujian</h4>
        <table>
            <tr><td>Nama Ujian</td><td>: {{ $ujian->nama_ujian }}</td></tr>
            <tr><td>Jenis Ujian</td><td>: {{ $ujian->jenis_ujian }}</td></tr>
            <tr><td>Mata Pelajaran</td><td>: {{ $ujian->mapel->nama_mapel ?? '-' }}</td></tr>
            <tr><td>Kelas</td><td>: {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td></tr>
            <tr><td>Semester</td><td>: {{ $ujian->tahunAjaran->semester ?? '-' }}</td></tr>
            <tr><td>Tahun Pelajaran</td><td>: {{ $ujian->tahunAjaran->tahun_ajaran ?? '-' }}</td></tr>
            <tr><td>Guru Mata Pelajaran</td><td>: {{ $ujian->guru->nama_guru ?? '-' }}</td></tr>
            <tr><td>KKTP/KKM</td><td>: {{ $ujian->kkm }}</td></tr>
        </table>
    </div>

    {{-- B. Kehadiran --}}
    <div class="section">
        <h4>B. Kehadiran Siswa</h4>
        <table>
            <tr><td>Jumlah Siswa</td><td>: {{ $ringkasan['jumlah_siswa'] }}</td></tr>
            <tr><td>Siswa Hadir</td><td>: {{ $ringkasan['hadir'] }}</td></tr>
            <tr><td>Siswa Tidak Hadir</td><td>: {{ $ringkasan['tidak_hadir'] }}</td></tr>
        </table>
    </div>

    {{-- C. Rentang Nilai --}}
    <div class="section">
        <h4>C. Rentang Nilai</h4>
        <table>
            <tr><td>Nilai &lt; KKTP/KKM</td><td>: {{ $rentang_nilai['bawah_kkm'] }}</td></tr>
            <tr><td>Nilai = KKTP/KKM</td><td>: {{ $rentang_nilai['sama_kkm'] }}</td></tr>
            <tr><td>Nilai &gt; KKTP/KKM</td><td>: {{ $rentang_nilai['atas_kkm'] }}</td></tr>
        </table>
    </div>

    {{-- D. Statistik Nilai --}}
    <div class="section">
        <h4>D. Statistik Nilai</h4>
        <table>
            <tr><td>Nilai Tertinggi</td><td>: {{ number_format($ringkasan['nilai_tertinggi'], 2) }}</td></tr>
            <tr><td>Nilai Terendah</td><td>: {{ number_format($ringkasan['nilai_terendah'], 2) }}</td></tr>
            <tr><td>Rata-rata Nilai</td><td>: {{ number_format($ringkasan['rata_rata'], 2) }}</td></tr>
        </table>
    </div>

    {{-- E. Ketuntasan --}}
    <div class="section">
        <h4>E. Ketuntasan</h4>
        <table>
            <tr><td>Tuntas</td><td>: {{ $ketuntasan['tuntas'] }}</td></tr>
            <tr><td>Belum Tuntas</td><td>: {{ $ketuntasan['belum_tuntas'] }}</td></tr>
        </table>
    </div>

    {{-- F. Ringkasan Analisis Soal --}}
    @if($analisis_ringkasan['total'] > 0)
    <div class="section">
        <h4>F. Ringkasan Analisis Soal</h4>
        <table>
            <tr><td>Jumlah Soal Dianalisis</td><td>: {{ $analisis_ringkasan['total'] }}</td></tr>
            <tr><td>Soal Baik (DP)</td><td>: {{ $analisis_ringkasan['soal_baik'] }}</td></tr>
            <tr><td>Soal Revisi</td><td>: {{ $analisis_ringkasan['soal_revisi'] }}</td></tr>
            <tr><td>Soal Buang</td><td>: {{ $analisis_ringkasan['soal_buang'] }}</td></tr>
            <tr><td>Soal Mudah (TK)</td><td>: {{ $analisis_ringkasan['soal_mudah'] }}</td></tr>
            <tr><td>Soal Sedang (TK)</td><td>: {{ $analisis_ringkasan['soal_sedang'] }}</td></tr>
            <tr><td>Soal Sukar (TK)</td><td>: {{ $analisis_ringkasan['soal_sukar'] }}</td></tr>
        </table>
    </div>
    @endif

    {{-- G. Tanda Tangan --}}
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

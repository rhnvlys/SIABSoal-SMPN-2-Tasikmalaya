<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Data T3 - {{ $ujian->nama_ujian }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #1F2937; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h3 { margin: 0; font-size: 12pt; }
        .header h2 { margin: 4px 0; font-size: 14pt; }
        .header p { margin: 2px 0; font-size: 9pt; color: #4B5563; }
        .header hr { border: 1px solid #1F2937; margin: 8px 0; }
        .info-table { width: 100%; margin-bottom: 16px; border: none; }
        .info-table td { padding: 2px 8px; font-size: 9pt; vertical-align: top; }
        .info-table td:first-child { font-weight: bold; width: 180px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 8pt; }
        table.data th, table.data td { border: 1px solid #9CA3AF; padding: 4px 6px; text-align: center; }
        table.data th { background: #E5E7EB; font-weight: bold; font-size: 7pt; }
        .ringkasan { margin-bottom: 16px; }
        .ringkasan h4 { font-size: 10pt; margin-bottom: 8px; }
        .ringkasan-grid { display: inline-block; }
        .ringkasan-grid td { padding: 2px 12px 2px 0; font-size: 9pt; }
        .badge { padding: 1px 6px; border-radius: 8px; font-size: 7pt; font-weight: bold; }
        .badge-green { background: #D1FAE5; color: #065F46; }
        .badge-amber { background: #FEF3C7; color: #92400E; }
        .badge-red   { background: #FEE2E2; color: #991B1B; }
        .badge-blue  { background: #DBEAFE; color: #1E40AF; }
        .badge-teal  { background: #CCFBF1; color: #115E59; }
        .footer { font-size: 8pt; color: #6B7280; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h3>SMP NEGERI 2 TASIKMALAYA</h3>
        <p>SIABSoal SMPN 2 Tasikmalaya</p>
        <hr>
        <h2>ANALISIS BUTIR SOAL</h2>
        <p>Daya Pembeda (DP) dan Tingkat Kesukaran (TK)</p>
    </div>

    {{-- Info Ujian --}}
    <table class="info-table">
        <tr><td>Nama Ujian</td><td>: {{ $ujian->nama_ujian }}</td></tr>
        <tr><td>Mata Pelajaran</td><td>: {{ $ujian->mapel->nama_mapel ?? '-' }}</td></tr>
        <tr><td>Kelas</td><td>: {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td></tr>
        <tr><td>Semester</td><td>: {{ $ujian->tahunAjaran->semester ?? '-' }}</td></tr>
        <tr><td>Tahun Pelajaran</td><td>: {{ $ujian->tahunAjaran->tahun_ajaran ?? '-' }}</td></tr>
        <tr><td>Guru</td><td>: {{ $ujian->guru->nama_guru ?? '-' }}</td></tr>
        <tr><td>Tanggal Cetak</td><td>: {{ now()->format('d F Y') }}</td></tr>
    </table>

    {{-- Ringkasan --}}
    <div class="ringkasan">
        <h4>Ringkasan Analisis</h4>
        <table class="ringkasan-grid">
            <tr><td>Soal Dianalisis</td><td>: {{ $ringkasan['total'] }}</td></tr>
            <tr><td>Soal Baik (DP)</td><td>: {{ $ringkasan['soal_baik'] }}</td></tr>
            <tr><td>Soal Revisi</td><td>: {{ $ringkasan['soal_revisi'] }}</td></tr>
            <tr><td>Soal Buang</td><td>: {{ $ringkasan['soal_buang'] }}</td></tr>
            <tr><td>Soal Mudah (TK)</td><td>: {{ $ringkasan['soal_mudah'] }}</td></tr>
            <tr><td>Soal Sedang (TK)</td><td>: {{ $ringkasan['soal_sedang'] }}</td></tr>
            <tr><td>Soal Sukar (TK)</td><td>: {{ $ringkasan['soal_sukar'] }}</td></tr>
        </table>
    </div>

    {{-- Tabel Hasil --}}
    <table class="data">
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
                <td>{{ $a->nomor_soal }}</td>
                <td>{{ $a->ba }}</td>
                <td>{{ $a->bb }}</td>
                <td>{{ $a->ja }}</td>
                <td>{{ $a->jb }}</td>
                <td>{{ number_format($a->dp, 3) }}</td>
                <td>
                    @if($a->kategori_dp === 'Baik')
                        <span class="badge badge-green">Baik</span>
                    @elseif($a->kategori_dp === 'Revisi')
                        <span class="badge badge-amber">Revisi</span>
                    @else
                        <span class="badge badge-red">Buang</span>
                    @endif
                </td>
                <td>{{ number_format($a->tk, 3) }}</td>
                <td>
                    @if($a->kategori_tk === 'Mudah')
                        <span class="badge badge-green">Mudah</span>
                    @elseif($a->kategori_tk === 'Sedang')
                        <span class="badge badge-blue">Sedang</span>
                    @else
                        <span class="badge badge-red">Sukar</span>
                    @endif
                </td>
                <td>
                    @if($a->keputusan === 'Dipakai')
                        <span class="badge badge-green">Dipakai</span>
                    @elseif($a->keputusan === 'Dipakai dengan catatan')
                        <span class="badge badge-teal">Dipakai*</span>
                    @elseif($a->keputusan === 'Revisi')
                        <span class="badge badge-amber">Revisi</span>
                    @else
                        <span class="badge badge-red">Buang</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak oleh SIABSoal SMPN 2 Tasikmalaya — {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>

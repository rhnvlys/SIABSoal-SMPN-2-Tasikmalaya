<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Olah Data T2 — {{ $ujian->nama_ujian }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #1a1a2e; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1a1a2e; padding-bottom: 8px; }
        .header h1 { font-size: 13px; margin-bottom: 2px; }
        .header h2 { font-size: 11px; font-weight: normal; }
        .header h3 { font-size: 10px; font-weight: normal; color: #555; }
        .header p { font-size: 8px; color: #666; }
        .school-logo { width: 50px; height: 50px; object-fit: contain; margin-bottom: 4px; }
        .info { margin-bottom: 10px; }
        .info td { padding: 1px 6px 1px 0; font-size: 9px; }
        .info td.label { font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #555; padding: 3px 5px; font-size: 9px; }
        table.data th { background: #1a1a2e; color: #fff; font-weight: bold; text-align: center; }
        .atas { background: #ecfdf5; }
        .bawah { background: #fef2f2; }
        .tengah { background: #fffbeb; }
        .ringkasan td { padding: 2px 8px; font-size: 9px; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        @if($sekolah && $sekolah->logoPath())
            <img src="{{ $sekolah->logoPath() }}" class="school-logo" alt="Logo sekolah">
        @endif
        <h1>{{ $sekolah->nama_sekolah ?? 'SMP NEGERI 2 TASIKMALAYA' }}</h1>
        <h2>SIABSoal SMPN 2 Tasikmalaya</h2>
        <h3>OLAH DATA T2 — RANKING DAN KELOMPOK</h3>
        <p>{{ $sekolah->alamat ?? '' }}</p>
    </div>

    <table class="info">
        <tr><td class="label">Ujian</td><td>: {{ $ujian->nama_ujian }}</td><td class="label" style="padding-left:20px">Guru</td><td>: {{ $ujian->guru->nama_guru ?? '-' }}</td></tr>
        <tr><td class="label">Mata Pelajaran</td><td>: {{ $ujian->mapel->nama_mapel ?? '-' }}</td><td class="label" style="padding-left:20px">Tahun Ajaran</td><td>: {{ $ujian->tahunAjaran->label ?? '-' }}</td></tr>
        <tr><td class="label">Metode</td><td>: {{ $ujian->metode_kelompok === 'persen_50' ? '50% Atas / 50% Bawah' : 'Manual' }}</td><td class="label" style="padding-left:20px">Kelas</td><td>: {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td></tr>
        <tr><td class="label">Tanggal Cetak</td><td>: {{ now()->format('d/m/Y H:i') }}</td><td></td><td></td></tr>
    </table>

    <table class="data">
        <thead>
            <tr><th>Ranking</th><th>NIS</th><th style="text-align:left">Nama Siswa</th><th>Nilai</th><th>Kelompok</th></tr>
        </thead>
        <tbody>
            @foreach($peserta as $p)
            <tr class="{{ $p->kelompok }}">
                <td style="text-align:center">{{ $p->ranking }}</td>
                <td>{{ $p->siswa->nis ?? '' }}</td>
                <td>{{ $p->siswa->nama_siswa ?? '' }}</td>
                <td style="text-align:center"><strong>{{ $p->nilai }}</strong></td>
                <td style="text-align:center">{{ strtoupper($p->kelompok ?? '') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="ringkasan">
        <tr><td colspan="2" style="font-weight:bold;padding-bottom:4px">Ringkasan:</td></tr>
        <tr><td>Kelompok Atas:</td><td>{{ $peserta->where('kelompok', 'atas')->count() }} siswa</td></tr>
        <tr><td>Kelompok Bawah:</td><td>{{ $peserta->where('kelompok', 'bawah')->count() }} siswa</td></tr>
        <tr><td>Kelompok Tengah:</td><td>{{ $peserta->where('kelompok', 'tengah')->count() }} siswa</td></tr>
    </table>

    <div class="footer">
        @if($sekolah && $sekolah->kepala_sekolah)
        <br><br><br>
        <p>{{ $sekolah->kepala_sekolah }}<br>NIP. {{ $sekolah->nip_kepala_sekolah ?? '-' }}</p>
        @endif
    </div>
</body>
</html>

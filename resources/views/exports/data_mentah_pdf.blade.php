<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mentah T1 — {{ $ujian->nama_ujian }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #1a1a2e; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1a1a2e; padding-bottom: 8px; }
        .header h1 { font-size: 13px; margin-bottom: 2px; }
        .header h2 { font-size: 11px; font-weight: normal; }
        .header h3 { font-size: 10px; font-weight: normal; color: #555; }
        .header p { font-size: 8px; color: #666; }
        .info { margin-bottom: 10px; }
        .info td { padding: 1px 6px 1px 0; font-size: 9px; }
        .info td.label { font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #555; padding: 2px 3px; text-align: center; font-size: 7px; }
        table.data th { background: #1a1a2e; color: #fff; font-weight: bold; }
        .kunci-row td { background: #e8f0fe; font-weight: bold; }
        .benar { color: #22c55e; }
        .salah { color: #ef4444; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $sekolah->nama_sekolah ?? 'SMP NEGERI 2 TASIKMALAYA' }}</h1>
        <h2>SIABSoal SMPN 2 Tasikmalaya</h2>
        <h3>DATA MENTAH T1 — SKOR JAWABAN SISWA</h3>
        <p>{{ $sekolah->alamat ?? '' }}</p>
    </div>

    <table class="info">
        <tr><td class="label">Ujian</td><td>: {{ $ujian->nama_ujian }}</td><td class="label" style="padding-left:20px">Guru</td><td>: {{ $ujian->guru->nama_guru ?? '-' }}</td></tr>
        <tr><td class="label">Mata Pelajaran</td><td>: {{ $ujian->mapel->nama_mapel ?? '-' }}</td><td class="label" style="padding-left:20px">Tahun Ajaran</td><td>: {{ $ujian->tahunAjaran->label ?? '-' }}</td></tr>
        <tr><td class="label">Jumlah Soal</td><td>: {{ $ujian->jumlah_soal }}</td><td class="label" style="padding-left:20px">Kelas</td><td>: {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td></tr>
        <tr><td class="label">KKM</td><td>: {{ $ujian->kkm }}</td><td class="label" style="padding-left:20px">Tanggal Cetak</td><td>: {{ now()->format('d/m/Y H:i') }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No</th><th>NIS</th><th>Nama Siswa</th><th>Hadir</th>
                @foreach($ujian->soal as $soal)<th>{{ $soal->nomor_soal }}</th>@endforeach
                <th>Benar</th><th>Salah</th><th>Nilai</th><th>Ket</th>
            </tr>
        </thead>
        <tbody>
            <tr class="kunci-row">
                <td></td><td></td><td style="text-align:left">KUNCI</td><td></td>
                @foreach($ujian->soal as $soal)<td>{{ $soal->kunci_jawaban ?? '-' }}</td>@endforeach
                <td></td><td></td><td></td><td></td>
            </tr>
            @foreach($peserta as $i => $p)
            @php $jawabanMap = $p->jawabanSiswa->keyBy('soal_id'); @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->siswa->nis ?? '' }}</td>
                <td style="text-align:left">{{ $p->siswa->nama_siswa ?? '' }}</td>
                <td>{{ $p->status_kehadiran === 'hadir' ? 'H' : 'TH' }}</td>
                @foreach($ujian->soal as $soal)
                    @php $js = $jawabanMap->get($soal->id); @endphp
                    <td class="{{ $js && $js->skor_biner ? 'benar' : 'salah' }}">{{ $js ? $js->skor_biner : 0 }}</td>
                @endforeach
                <td><strong>{{ $p->jumlah_benar }}</strong></td>
                <td>{{ $p->jumlah_salah }}</td>
                <td><strong>{{ $p->nilai }}</strong></td>
                <td>{{ $p->keterangan === 'tercapai' ? 'T' : 'PP' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        @if($sekolah && $sekolah->kepala_sekolah)
        <br><br><br>
        <p>{{ $sekolah->kepala_sekolah }}<br>NIP. {{ $sekolah->nip_kepala_sekolah ?? '-' }}</p>
        @endif
    </div>
</body>
</html>

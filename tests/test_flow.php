<?php

// Quick test script — run with: php tests/test_flow.php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== T1 DATA MENTAH ===\n";
$scoringService = app(\App\Services\ScoringService::class);
$ujian = \App\Models\Ujian::first();
echo "Ujian: {$ujian->nama_ujian}\n";
echo "Status sebelum: {$ujian->status}\n";

$hasil = $scoringService->prosesJawabanABCD($ujian->id);
$ujian->refresh();
echo "Status sesudah T1: {$ujian->status}\n";

$peserta = \App\Models\PesertaUjian::where('ujian_id', $ujian->id)
    ->with('siswa')
    ->orderByDesc('nilai')
    ->get();

echo "\n--- Nilai Siswa ---\n";
foreach ($peserta as $p) {
    printf("%-30s | Benar: %2d | Nilai: %6.2f | %s\n",
        $p->siswa->nama_siswa, $p->jumlah_benar, $p->nilai, $p->keterangan);
}

echo "\n=== T2 OLAH DATA ===\n";
$groupingService = app(\App\Services\GroupingService::class);
$hasilT2 = $groupingService->prosesKelompok($ujian->id);
$ujian->refresh();
echo "Status sesudah T2: {$ujian->status}\n";
echo "Hadir: {$hasilT2['jumlah_hadir']}, Kelompok: {$hasilT2['jumlah_kelompok']}\n";

$peserta = \App\Models\PesertaUjian::where('ujian_id', $ujian->id)
    ->where('status_kehadiran', 'hadir')
    ->with('siswa')
    ->orderBy('ranking')
    ->get();

echo "\n--- Ranking & Kelompok ---\n";
foreach ($peserta as $p) {
    printf("Rank %2d | %-30s | Nilai: %6.2f | %s\n",
        $p->ranking, $p->siswa->nama_siswa, $p->nilai, strtoupper($p->kelompok));
}

echo "\n=== T3 ANALISIS DATA ===\n";
$analysisService = app(\App\Services\ItemAnalysisService::class);
$hasilT3 = $analysisService->prosesAnalisis($ujian->id);
$ujian->refresh();
echo "Status sesudah T3: {$ujian->status}\n";

echo "\n--- Analisis Butir Soal ---\n";
printf("%-4s%-4s%-4s%-4s%-4s%-8s%-8s%-8s%-8s%s\n", 'No','BA','BB','JA','JB','DP','Kat.DP','TK','Kat.TK','Keputusan');
echo str_repeat('-', 70) . "\n";
foreach ($hasilT3 as $a) {
    printf("%2d  %2d  %2d  %2d  %2d  %6.3f  %-6s  %6.3f  %-6s  %s\n",
        $a->nomor_soal, $a->ba, $a->bb, $a->ja, $a->jb,
        $a->dp, $a->kategori_dp, $a->tk, $a->kategori_tk, $a->keputusan);
}

echo "\n=== T4 DAFTAR NILAI ===\n";
$reportService = app(\App\Services\ReportService::class);
$daftarNilai = $reportService->getDaftarNilai($ujian->id);
$r = $daftarNilai['ringkasan'];
echo "Siswa: {$r['jumlah_siswa']} | Hadir: {$r['hadir']} | Tdk Hadir: {$r['tidak_hadir']}\n";
echo "Max: {$r['nilai_tertinggi']} | Min: {$r['nilai_terendah']} | Avg: {$r['rata_rata']}\n";
echo "Tercapai: {$r['jumlah_tercapai']} | Perlu Peningkatan: {$r['jumlah_perlu_peningkatan']}\n";

echo "\n=== T5 REKAP NILAI ===\n";
$rekapNilai = $reportService->getRekapNilai($ujian->id);
$rv = $rekapNilai['rentang_nilai'];
$kt = $rekapNilai['ketuntasan'];
echo "Bawah KKM: {$rv['bawah_kkm']} | Sama KKM: {$rv['sama_kkm']} | Atas KKM: {$rv['atas_kkm']}\n";
echo "Tuntas: {$kt['tuntas']} | Belum Tuntas: {$kt['belum_tuntas']}\n";

echo "\n✅ SEMUA TAHAP T1-T5 BERHASIL!\n";

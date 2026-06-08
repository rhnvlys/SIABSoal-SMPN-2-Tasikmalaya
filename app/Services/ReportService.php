<?php

namespace App\Services;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\AnalisisButir;
use App\Models\PengaturanSekolah;

/**
 * ReportService — Tahap 4 & 5 (T4/T5) Daftar Nilai & Rekap Nilai
 *
 * Menyediakan data untuk halaman Daftar Nilai dan Rekap Nilai.
 * Sesuai arahan sekolah: tidak menampilkan validitas, korelasi, reliabilitas.
 */
class ReportService
{
    /**
     * Ambil data daftar nilai untuk ujian tertentu (T4).
     */
    public function getDaftarNilai(int $ujianId): array
    {
        $ujian = Ujian::with(['guru', 'mapel', 'tahunAjaran', 'kelas'])->findOrFail($ujianId);
        $sekolah = PengaturanSekolah::getSettings();

        $peserta = PesertaUjian::where('ujian_id', $ujianId)
            ->with('siswa')
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->get();

        // Ringkasan
        $hadir      = $peserta->where('status_kehadiran', 'hadir');
        $tidakHadir = $peserta->where('status_kehadiran', 'tidak_hadir');
        $nilaiHadir = $hadir->pluck('nilai');

        $ringkasan = [
            'jumlah_siswa'       => $peserta->count(),
            'hadir'              => $hadir->count(),
            'tidak_hadir'        => $tidakHadir->count(),
            'nilai_tertinggi'    => $nilaiHadir->max() ?? 0,
            'nilai_terendah'     => $nilaiHadir->min() ?? 0,
            'rata_rata'          => $nilaiHadir->count() > 0 ? round($nilaiHadir->avg(), 2) : 0,
            'jumlah_tercapai'    => $hadir->where('keterangan', 'tercapai')->count(),
            'jumlah_perlu_peningkatan' => $hadir->where('keterangan', 'perlu_peningkatan')->count(),
        ];

        return [
            'ujian'     => $ujian,
            'sekolah'   => $sekolah,
            'peserta'   => $peserta,
            'ringkasan' => $ringkasan,
        ];
    }

    /**
     * Ambil data rekap nilai untuk ujian tertentu (T5).
     */
    public function getRekapNilai(int $ujianId): array
    {
        $daftarNilai = $this->getDaftarNilai($ujianId);
        $ujian       = $daftarNilai['ujian'];
        $peserta     = $daftarNilai['peserta'];
        $hadir       = $peserta->where('status_kehadiran', 'hadir');
        $kkm         = (float) $ujian->kktp_value;

        // Rentang nilai
        $nilaiHadir  = $hadir->pluck('nilai');
        $bawahKkm    = $nilaiHadir->filter(fn($v) => $v < $kkm)->count();
        $samaKkm     = $nilaiHadir->filter(fn($v) => (float)$v == $kkm)->count();
        $atasKkm     = $nilaiHadir->filter(fn($v) => $v > $kkm)->count();

        // Ketuntasan
        $tuntas      = $nilaiHadir->filter(fn($v) => $v >= $kkm)->count();
        $belumTuntas = $nilaiHadir->filter(fn($v) => $v < $kkm)->count();

        // Ringkasan analisis soal (hanya DP & TK)
        $analisisRingkasan = $this->getRingkasanAnalisis($ujianId);

        $kesimpulan = $this->buildKesimpulan($tuntas, $belumTuntas, $analisisRingkasan);

        return array_merge($daftarNilai, [
            'rentang_nilai' => [
                'bawah_kkm' => $bawahKkm,
                'sama_kkm'  => $samaKkm,
                'atas_kkm'  => $atasKkm,
            ],
            'ketuntasan' => [
                'tuntas'              => $tuntas,
                'tuntas_dengan_remedi' => 0,
                'belum_tuntas'        => $belumTuntas,
            ],
            'analisis_ringkasan' => $analisisRingkasan,
            'kesimpulan' => $kesimpulan,
        ]);
    }

    /**
     * Ringkasan analisis soal — hanya DP & TK sesuai arahan sekolah.
     */
    public function getRingkasanAnalisis(int $ujianId): array
    {
        $analisis = AnalisisButir::where('ujian_id', $ujianId)->get();

        return [
            'total'       => $analisis->count(),
            // Daya Pembeda
            'soal_baik'   => $analisis->where('kategori_dp', 'Baik')->count(),
            'soal_revisi' => $analisis->where('keputusan', 'Revisi')->count(),
            'soal_buang'  => $analisis->where('keputusan', 'Buang')->count(),
            // Tingkat Kesukaran
            'soal_mudah'  => $analisis->where('kategori_tk', 'Mudah')->count(),
            'soal_sedang' => $analisis->where('kategori_tk', 'Sedang')->count(),
            'soal_sukar'  => $analisis->where('kategori_tk', 'Sukar')->count(),
            // Keputusan
            'rekomendasi_dipakai' => $analisis->where('keputusan', 'Dipakai')->count(),
            'rekomendasi_dipakai_catatan' => $analisis->where('keputusan', 'Dipakai dengan catatan')->count(),
            'rekomendasi_revisi'  => $analisis->where('keputusan', 'Revisi')->count(),
            'rekomendasi_buang'   => $analisis->where('keputusan', 'Buang')->count(),
        ];
    }

    private function buildKesimpulan(int $tuntas, int $belumTuntas, array $analisisRingkasan): string
    {
        $ketuntasan = $belumTuntas === 0
            ? 'seluruh siswa hadir sudah mencapai KKTP/KKM'
            : "{$belumTuntas} siswa masih perlu peningkatan";

        if (($analisisRingkasan['total'] ?? 0) === 0) {
            return "Rekap nilai menunjukkan {$ketuntasan}. Analisis butir soal belum tersedia.";
        }

        return "Rekap nilai menunjukkan {$ketuntasan}. Hasil analisis menemukan {$analisisRingkasan['soal_baik']} soal baik, {$analisisRingkasan['soal_revisi']} soal revisi, dan {$analisisRingkasan['soal_buang']} soal buang.";
    }
}

<?php

namespace App\Services;

use App\Models\AnalisisButir;
use App\Models\PengaturanSekolah;
use App\Models\PesertaUjian;
use App\Models\Ujian;

/**
 * Data T4/T5 dihitung lewat aggregate database. Halaman T4 memakai pagination,
 * sedangkan export dapat meminta seluruh baris secara eksplisit.
 */
class ReportService
{
    public function getDaftarNilai(int $ujianId, ?int $perPage = null): array
    {
        $ujian = $this->reportUjian($ujianId);
        $query = PesertaUjian::where('ujian_id', $ujianId)
            ->with('siswa:id,nis,nisn,nama_siswa,jenis_kelamin')
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*');

        $peserta = $perPage === null
            ? $query->get()
            : $query->paginate($perPage)->withQueryString();

        return [
            'ujian' => $ujian,
            'sekolah' => PengaturanSekolah::getSettings(),
            'peserta' => $peserta,
            'ringkasan' => $this->nilaiSummary($ujianId),
        ];
    }

    public function getRekapNilai(int $ujianId): array
    {
        $ujian = $this->reportUjian($ujianId);
        $ringkasan = $this->nilaiSummary($ujianId);
        $kkm = (float) $ujian->kktp_value;

        $distribution = PesertaUjian::where('ujian_id', $ujianId)
            ->where('status_kehadiran', 'hadir')
            ->selectRaw('SUM(CASE WHEN nilai < ? THEN 1 ELSE 0 END) AS bawah_kkm', [$kkm])
            ->selectRaw('SUM(CASE WHEN nilai = ? THEN 1 ELSE 0 END) AS sama_kkm', [$kkm])
            ->selectRaw('SUM(CASE WHEN nilai > ? THEN 1 ELSE 0 END) AS atas_kkm', [$kkm])
            ->selectRaw('SUM(CASE WHEN nilai >= ? THEN 1 ELSE 0 END) AS tuntas', [$kkm])
            ->selectRaw('SUM(CASE WHEN nilai < ? THEN 1 ELSE 0 END) AS belum_tuntas', [$kkm])
            ->first();

        $analisisRingkasan = $this->getRingkasanAnalisis($ujianId);
        $tuntas = (int) ($distribution->tuntas ?? 0);
        $belumTuntas = (int) ($distribution->belum_tuntas ?? 0);

        return [
            'ujian' => $ujian,
            'sekolah' => PengaturanSekolah::getSettings(),
            'ringkasan' => $ringkasan,
            'rentang_nilai' => [
                'bawah_kkm' => (int) ($distribution->bawah_kkm ?? 0),
                'sama_kkm' => (int) ($distribution->sama_kkm ?? 0),
                'atas_kkm' => (int) ($distribution->atas_kkm ?? 0),
            ],
            'ketuntasan' => [
                'tuntas' => $tuntas,
                'tuntas_dengan_remedi' => 0,
                'belum_tuntas' => $belumTuntas,
            ],
            'analisis_ringkasan' => $analisisRingkasan,
            'kesimpulan' => $this->buildKesimpulan($tuntas, $belumTuntas, $analisisRingkasan),
        ];
    }

    public function getRingkasanAnalisis(int $ujianId): array
    {
        $summary = AnalisisButir::where('ujian_id', $ujianId)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN kategori_dp = 'Baik' THEN 1 ELSE 0 END) AS soal_baik")
            ->selectRaw("SUM(CASE WHEN keputusan = 'Revisi' THEN 1 ELSE 0 END) AS soal_revisi")
            ->selectRaw("SUM(CASE WHEN keputusan = 'Buang' THEN 1 ELSE 0 END) AS soal_buang")
            ->selectRaw("SUM(CASE WHEN kategori_tk = 'Mudah' THEN 1 ELSE 0 END) AS soal_mudah")
            ->selectRaw("SUM(CASE WHEN kategori_tk = 'Sedang' THEN 1 ELSE 0 END) AS soal_sedang")
            ->selectRaw("SUM(CASE WHEN kategori_tk = 'Sukar' THEN 1 ELSE 0 END) AS soal_sukar")
            ->selectRaw("SUM(CASE WHEN keputusan = 'Dipakai' THEN 1 ELSE 0 END) AS rekomendasi_dipakai")
            ->selectRaw("SUM(CASE WHEN keputusan = 'Dipakai dengan catatan' THEN 1 ELSE 0 END) AS rekomendasi_dipakai_catatan")
            ->first();

        return [
            'total' => (int) ($summary->total ?? 0),
            'soal_baik' => (int) ($summary->soal_baik ?? 0),
            'soal_revisi' => (int) ($summary->soal_revisi ?? 0),
            'soal_buang' => (int) ($summary->soal_buang ?? 0),
            'soal_mudah' => (int) ($summary->soal_mudah ?? 0),
            'soal_sedang' => (int) ($summary->soal_sedang ?? 0),
            'soal_sukar' => (int) ($summary->soal_sukar ?? 0),
            'rekomendasi_dipakai' => (int) ($summary->rekomendasi_dipakai ?? 0),
            'rekomendasi_dipakai_catatan' => (int) ($summary->rekomendasi_dipakai_catatan ?? 0),
            'rekomendasi_revisi' => (int) ($summary->soal_revisi ?? 0),
            'rekomendasi_buang' => (int) ($summary->soal_buang ?? 0),
        ];
    }

    private function reportUjian(int $ujianId): Ujian
    {
        return Ujian::with(['guru', 'mapel', 'tahunAjaran', 'kelas'])->findOrFail($ujianId);
    }

    private function nilaiSummary(int $ujianId): array
    {
        $summary = PesertaUjian::where('ujian_id', $ujianId)
            ->selectRaw('COUNT(*) AS jumlah_siswa')
            ->selectRaw("SUM(CASE WHEN status_kehadiran = 'hadir' THEN 1 ELSE 0 END) AS hadir")
            ->selectRaw("SUM(CASE WHEN status_kehadiran = 'tidak_hadir' THEN 1 ELSE 0 END) AS tidak_hadir")
            ->selectRaw("MAX(CASE WHEN status_kehadiran = 'hadir' THEN nilai END) AS nilai_tertinggi")
            ->selectRaw("MIN(CASE WHEN status_kehadiran = 'hadir' THEN nilai END) AS nilai_terendah")
            ->selectRaw("AVG(CASE WHEN status_kehadiran = 'hadir' THEN nilai END) AS rata_rata")
            ->selectRaw("SUM(CASE WHEN status_kehadiran = 'hadir' AND keterangan = 'tercapai' THEN 1 ELSE 0 END) AS jumlah_tercapai")
            ->selectRaw("SUM(CASE WHEN status_kehadiran = 'hadir' AND keterangan = 'perlu_peningkatan' THEN 1 ELSE 0 END) AS jumlah_perlu_peningkatan")
            ->first();

        return [
            'jumlah_siswa' => (int) ($summary->jumlah_siswa ?? 0),
            'hadir' => (int) ($summary->hadir ?? 0),
            'tidak_hadir' => (int) ($summary->tidak_hadir ?? 0),
            'nilai_tertinggi' => (float) ($summary->nilai_tertinggi ?? 0),
            'nilai_terendah' => (float) ($summary->nilai_terendah ?? 0),
            'rata_rata' => round((float) ($summary->rata_rata ?? 0), 2),
            'jumlah_tercapai' => (int) ($summary->jumlah_tercapai ?? 0),
            'jumlah_perlu_peningkatan' => (int) ($summary->jumlah_perlu_peningkatan ?? 0),
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

<?php

namespace App\Services;

use App\Models\AnalisisButir;
use App\Models\JawabanSiswa;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;

/**
 * ItemAnalysisService — Tahap 3 (T3) Analisis Data
 *
 * Sesuai arahan sekolah, hanya menghitung:
 * - Daya Pembeda (DP)
 * - Tingkat Kesukaran (TK)
 *
 * Tidak menghitung: validitas, korelasi, regresi, uji beda, r tabel,
 * reliabilitas, pengecoh, atau statistik lanjutan lainnya.
 *
 * Definisi:
 *   BA = jumlah siswa kelompok atas yang menjawab benar pada soal tertentu
 *   BB = jumlah siswa kelompok bawah yang menjawab benar pada soal tertentu
 *   JA = jumlah siswa kelompok atas
 *   JB = jumlah siswa kelompok bawah
 *   N  = JA + JB
 *
 * Rumus:
 *   DP = (BA - BB) / JA
 *   TK = (BA + BB) / N
 */
class ItemAnalysisService
{
    /**
     * Proses analisis butir soal.
     *
     * Alur:
     * 1. Validasi T2 sudah selesai
     * 2. Ambil peserta kelompok atas dan bawah
     * 3. Validasi JA == JB dan keduanya > 0
     * 4. Untuk setiap soal, hitung BA, BB, DP, TK
     * 5. Tentukan kategori dan keputusan
     * 6. Simpan ke analisis_butir (hapus hasil lama dulu)
     * 7. Update status ujian → dianalisis
     */
    public function prosesAnalisis(int $ujianId): array
    {
        $ujian = Ujian::with('soal')->findOrFail($ujianId);

        // Validasi: T2 harus sudah diproses
        if (!in_array($ujian->status, ['olah_data', 'dianalisis', 'selesai'])) {
            throw new \Exception('Olah Data (T2) belum diproses. Silakan proses Olah Data terlebih dahulu.');
        }

        $jumlahJawaban = JawabanSiswa::whereHas('pesertaUjian', function ($query) use ($ujianId) {
            $query->where('ujian_id', $ujianId);
        })->count();

        if ($jumlahJawaban === 0) {
            throw new \Exception('Jawaban siswa belum tersedia. Proses Data Mentah T1 terlebih dahulu.');
        }

        // Ambil peserta kelompok atas dan bawah
        $pesertaAtas = PesertaUjian::where('ujian_id', $ujianId)
            ->where('status_kehadiran', 'hadir')
            ->where('kelompok', 'atas')
            ->pluck('id')
            ->values();

        $pesertaBawah = PesertaUjian::where('ujian_id', $ujianId)
            ->where('status_kehadiran', 'hadir')
            ->where('kelompok', 'bawah')
            ->pluck('id')
            ->values();

        $ja = $pesertaAtas->count();
        $jb = $pesertaBawah->count();

        // Validasi: JA dan JB harus > 0
        if ($ja === 0 || $jb === 0) {
            throw new \Exception('Kelompok atas atau bawah belum tersedia. Proses Olah Data terlebih dahulu.');
        }

        // Validasi: JA dan JB harus sama
        if ($ja !== $jb) {
            throw new \Exception('Jumlah kelompok atas dan kelompok bawah harus seimbang. Atas: ' . $ja . ', Bawah: ' . $jb);
        }

        $n = $ja + $jb;
        $hasilAnalisis = [];

        DB::transaction(function () use ($ujian, $pesertaAtas, $pesertaBawah, $ja, $jb, $n, &$hasilAnalisis) {
            // Hapus hasil lama (agar tidak dobel jika proses ulang)
            AnalisisButir::where('ujian_id', $ujian->id)->delete();

            foreach ($ujian->soal as $soal) {
                // Hitung BA: jumlah kelompok atas yang benar
                $ba = JawabanSiswa::whereIn('peserta_ujian_id', $pesertaAtas)
                    ->where('soal_id', $soal->id)
                    ->where('skor_biner', 1)
                    ->count();

                // Hitung BB: jumlah kelompok bawah yang benar
                $bb = JawabanSiswa::whereIn('peserta_ujian_id', $pesertaBawah)
                    ->where('soal_id', $soal->id)
                    ->where('skor_biner', 1)
                    ->count();

                // Hitung DP dan TK
                $dp = $this->hitungDP($ba, $bb, $ja);
                $tk = $this->hitungTK($ba, $bb, $n);

                // Tentukan kategori
                $kategoriDp = $this->kategoriDP($dp);
                $kategoriTk = $this->kategoriTK($tk);

                // Tentukan keputusan soal
                $keputusan = $this->tentukanKeputusan($kategoriDp, $kategoriTk);

                // Simpan hasil
                $analisis = AnalisisButir::create([
                    'ujian_id'             => $ujian->id,
                    'soal_id'              => $soal->id,
                    'nomor_soal'           => $soal->nomor_soal,
                    'ba'                   => $ba,
                    'bb'                   => $bb,
                    'ja'                   => $ja,
                    'jb'                   => $jb,
                    'n_analisis'           => $n,
                    'jumlah_benar'         => $ba + $bb, // Total benar dari kedua kelompok
                    'jumlah_peserta_valid' => $n,
                    'dp'                   => $dp,
                    'kategori_dp'          => $kategoriDp,
                    'tk'                   => $tk,
                    'kategori_tk'          => $kategoriTk,
                    'keputusan'            => $keputusan,
                    // Kolom lama dibiarkan null — tidak digunakan
                ]);

                $hasilAnalisis[] = $analisis;
            }

            $ujian->update(['status' => 'dianalisis']);
        });

        return $hasilAnalisis;
    }

    /**
     * Hitung Daya Pembeda.
     *
     * Rumus: DP = (BA - BB) / JA
     *
     * @param int $ba Jumlah benar kelompok atas
     * @param int $bb Jumlah benar kelompok bawah
     * @param int $ja Jumlah siswa kelompok atas
     * @return float
     */
    public function hitungDP(int $ba, int $bb, int $ja): float
    {
        if ($ja === 0) return 0;

        return round(($ba - $bb) / $ja, 3);
    }

    /**
     * Kategori Daya Pembeda sesuai arahan sekolah.
     *
     * - DP >= 0.40 → Baik
     * - DP >= 0.21 dan DP < 0.40 → Revisi
     * - DP < 0.21 → Buang
     */
    public function kategoriDP(float $dp): string
    {
        if ($dp >= 0.40) return 'Baik';
        if ($dp >= 0.21) return 'Revisi';
        return 'Buang';
    }

    /**
     * Hitung Tingkat Kesukaran.
     *
     * Rumus: TK = (BA + BB) / N
     *
     * @param int $ba Jumlah benar kelompok atas
     * @param int $bb Jumlah benar kelompok bawah
     * @param int $n  Jumlah siswa kelompok atas + bawah (JA + JB)
     * @return float
     */
    public function hitungTK(int $ba, int $bb, int $n): float
    {
        if ($n === 0) return 0;

        return round(($ba + $bb) / $n, 3);
    }

    /**
     * Kategori Tingkat Kesukaran sesuai arahan sekolah.
     *
     * - TK > 0.79 → Mudah
     * - TK >= 0.30 dan TK <= 0.79 → Sedang
     * - TK < 0.30 → Sukar
     */
    public function kategoriTK(float $tk): string
    {
        if ($tk > 0.79) return 'Mudah';
        if ($tk >= 0.30) return 'Sedang';
        return 'Sukar';
    }

    /**
     * Tentukan keputusan soal berdasarkan kombinasi DP dan TK.
     *
     * Matriks keputusan:
     * - DP Baik + TK Sedang → Dipakai
     * - DP Baik + TK Mudah → Dipakai dengan catatan
     * - DP Baik + TK Sukar → Dipakai dengan catatan
     * - DP Revisi → Revisi
     * - DP Buang → Buang
     */
    public function tentukanKeputusan(string $kategoriDp, string $kategoriTk): string
    {
        if ($kategoriDp === 'Baik') {
            if ($kategoriTk === 'Sedang') {
                return 'Dipakai';
            }
            return 'Dipakai dengan catatan';
        }

        if ($kategoriDp === 'Revisi') {
            return 'Revisi';
        }

        return 'Buang';
    }
}

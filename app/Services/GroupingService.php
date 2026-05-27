<?php

namespace App\Services;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\AnalisisButir;
use Illuminate\Support\Facades\DB;

/**
 * GroupingService — Tahap 2 (T2) Olah Data
 *
 * Mengurutkan siswa berdasarkan nilai dan
 * membagi kelompok atas/bawah untuk keperluan analisis butir soal.
 */
class GroupingService
{
    /**
     * Proses pengelompokan peserta ujian.
     *
     * Alur:
     * 1. Ambil peserta ujian yang hadir, urutkan berdasarkan nilai DESC, nama ASC
     * 2. Hitung jumlah siswa hadir
     * 3. Tentukan jumlah kelompok berdasarkan metode (50% atau manual)
     * 4. Bagi menjadi kelompok atas, bawah, dan tengah (jika ganjil)
     * 5. Simpan ranking dan kelompok ke peserta_ujian
     * 6. Update status ujian menjadi 'olah_data'
     *
     * @throws \Exception jika jumlah siswa terlalu sedikit atau validasi gagal
     */
    public function prosesKelompok(int $ujianId): array
    {
        $ujian = Ujian::findOrFail($ujianId);

        // Validasi: Data Mentah T1 harus sudah ada
        if (!in_array($ujian->status, ['data_mentah', 'olah_data', 'dianalisis', 'selesai'])) {
            throw new \Exception('Data Mentah (T1) belum diproses. Silakan proses Data Mentah terlebih dahulu.');
        }

        $jumlahJawaban = JawabanSiswa::whereHas('pesertaUjian', function ($query) use ($ujianId) {
            $query->where('ujian_id', $ujianId);
        })->count();

        if ($jumlahJawaban === 0) {
            throw new \Exception('Jawaban siswa belum tersedia. Proses Data Mentah T1 terlebih dahulu.');
        }

        // Ambil peserta hadir, urutkan nilai tertinggi ke terendah
        // Jika nilai sama, urutkan berdasarkan nama siswa agar stabil
        $pesertaHadir = PesertaUjian::where('ujian_id', $ujianId)
            ->where('status_kehadiran', 'hadir')
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderByDesc('peserta_ujian.nilai')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->get();

        $jumlahHadir = $pesertaHadir->count();

        // Validasi minimal
        if ($jumlahHadir < 2) {
            throw new \Exception('Jumlah siswa hadir terlalu sedikit (minimal 2 siswa).');
        }

        // Hitung jumlah kelompok
        $jumlahKelompok = $this->hitungJumlahKelompok(
            $jumlahHadir,
            $ujian->metode_kelompok,
            $ujian->jumlah_kelompok_manual
        );

        $warning = null;
        if ($jumlahHadir < 4) {
            $warning = 'Jumlah siswa terlalu sedikit, hasil analisis perlu ditinjau kembali.';
        }

        // Simpan ranking dan kelompok
        $this->simpanRankingDanKelompok($ujianId, $pesertaHadir, $jumlahKelompok);

        AnalisisButir::where('ujian_id', $ujian->id)->delete();
        $ujian->update(['status' => 'olah_data']);

        return [
            'jumlah_hadir'     => $jumlahHadir,
            'jumlah_kelompok'  => $jumlahKelompok,
            'metode'           => $ujian->metode_kelompok,
            'warning'          => $warning,
        ];
    }

    /**
     * Hitung jumlah siswa per kelompok.
     *
     * Metode persen_50:
     *   jumlah_kelompok = floor(jumlah_hadir / 2)
     *
     * Metode manual:
     *   jumlah_kelompok = jumlah_kelompok_manual
     *   Validasi: jumlah_kelompok * 2 <= jumlah_hadir
     *
     * Contoh: 35 siswa hadir
     *   jumlah_kelompok = floor(35/2) = 17
     *   17 atas, 17 bawah, 1 tengah
     */
    public function hitungJumlahKelompok(int $jumlahHadir, string $metode, ?int $jumlahManual = null): int
    {
        if ($metode === 'manual') {
            if ($jumlahManual === null || $jumlahManual <= 0) {
                throw new \Exception('Jumlah kelompok manual wajib lebih dari 0.');
            }

            // Validasi: jumlah_kelompok * 2 tidak boleh melebihi jumlah hadir
            if (($jumlahManual * 2) > $jumlahHadir) {
                throw new \Exception(
                    "Jumlah kelompok manual ({$jumlahManual} × 2 = " . ($jumlahManual * 2) .
                    ") melebihi jumlah siswa hadir ({$jumlahHadir})."
                );
            }
            return $jumlahManual;
        }

        // Default: 50% atas, 50% bawah
        return (int) floor($jumlahHadir / 2);
    }

    /**
     * Simpan ranking dan kelompok ke peserta_ujian.
     *
     * Pembagian:
     *   - Ranking 1 s/d jumlah_kelompok     = kelompok ATAS
     *   - Ranking terakhir jumlah_kelompok   = kelompok BAWAH
     *   - Siswa di tengah (jika ganjil)      = kelompok TENGAH
     */
    public function simpanRankingDanKelompok(int $ujianId, $pesertaHadir, int $jumlahKelompok): void
    {
        DB::transaction(function () use ($ujianId, $pesertaHadir, $jumlahKelompok) {
            $jumlahHadir = $pesertaHadir->count();

            // Reset ranking dan kelompok untuk yang tidak hadir
            PesertaUjian::where('ujian_id', $ujianId)
                ->where('status_kehadiran', 'tidak_hadir')
                ->update(['ranking' => null, 'kelompok' => null]);

            // Berikan ranking dan kelompok untuk yang hadir
            foreach ($pesertaHadir->values() as $index => $peserta) {
                $ranking = $index + 1;

                if ($ranking <= $jumlahKelompok) {
                    // Kelompok ATAS (nilai tertinggi)
                    $kelompok = 'atas';
                } elseif ($ranking > ($jumlahHadir - $jumlahKelompok)) {
                    // Kelompok BAWAH (nilai terendah)
                    $kelompok = 'bawah';
                } else {
                    // Kelompok TENGAH (siswa di tengah, jika ganjil)
                    $kelompok = 'tengah';
                }

                PesertaUjian::where('id', $peserta->id)->update([
                    'ranking'  => $ranking,
                    'kelompok' => $kelompok,
                ]);
            }
        });
    }
}

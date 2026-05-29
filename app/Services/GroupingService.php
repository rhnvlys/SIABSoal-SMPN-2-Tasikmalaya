<?php

namespace App\Services;

use App\Models\AnalisisButir;
use App\Models\JawabanSiswa;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;

/**
 * GroupingService - Tahap 2 (T2) Olah Data.
 *
 * Mengurutkan siswa berdasarkan nilai dan membagi peserta hadir menjadi
 * kelompok atas, bawah, dan tengah untuk analisis butir soal.
 */
class GroupingService
{
    /**
     * Proses pengelompokan peserta ujian.
     *
     * @throws \Exception jika data T1 belum siap atau jumlah kelompok tidak valid
     */
    public function prosesKelompok(int $ujianId): array
    {
        $ujian = Ujian::findOrFail($ujianId);

        if (!in_array($ujian->status, ['data_mentah', 'olah_data', 'dianalisis', 'selesai'])) {
            throw new \Exception('Data Mentah (T1) belum diproses. Silakan proses Data Mentah terlebih dahulu.');
        }

        $jumlahJawaban = JawabanSiswa::whereHas('pesertaUjian', function ($query) use ($ujianId) {
            $query->where('ujian_id', $ujianId);
        })->count();

        if ($jumlahJawaban === 0) {
            throw new \Exception('Jawaban siswa belum tersedia. Proses Data Mentah T1 terlebih dahulu.');
        }

        $pesertaHadir = PesertaUjian::where('ujian_id', $ujianId)
            ->where('status_kehadiran', 'hadir')
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderByDesc('peserta_ujian.nilai')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->get();

        $jumlahHadir = $pesertaHadir->count();

        if ($jumlahHadir < 2) {
            throw new \Exception('Jumlah siswa hadir terlalu sedikit (minimal 2 siswa).');
        }

        $jumlahKelompok = $this->hitungJumlahKelompok(
            $jumlahHadir,
            $ujian->metode_kelompok,
            $ujian->jumlah_kelompok_manual
        );

        $warning = null;
        if ($jumlahHadir < 4) {
            $warning = 'Jumlah siswa terlalu sedikit, hasil analisis perlu ditinjau kembali.';
        }

        $this->simpanRankingDanKelompok($ujianId, $pesertaHadir, $jumlahKelompok);

        AnalisisButir::where('ujian_id', $ujian->id)->delete();
        $ujian->update(['status' => 'olah_data']);

        return [
            'jumlah_hadir' => $jumlahHadir,
            'jumlah_kelompok' => $jumlahKelompok,
            'metode' => $ujian->metode_kelompok,
            'warning' => $warning,
        ];
    }

    /**
     * Hitung jumlah siswa per kelompok.
     *
     * Manual dan otomatis selalu dibatasi floor(jumlah_hadir / 2), sehingga
     * kelompok atas dan bawah tidak overlap. Jika jumlah hadir ganjil, satu
     * siswa tengah tidak masuk perhitungan DP/TK.
     */
    public function hitungJumlahKelompok(int $jumlahHadir, string $metode, ?int $jumlahManual = null): int
    {
        $maksimalKelompok = (int) floor($jumlahHadir / 2);

        if ($metode === 'manual') {
            if ($jumlahManual === null || $jumlahManual <= 0) {
                throw new \Exception('Jumlah kelompok manual wajib lebih dari 0.');
            }

            if ($jumlahManual > $maksimalKelompok) {
                throw new \Exception(
                    "Jumlah kelompok terlalu besar. Maksimal untuk jumlah siswa hadir {$jumlahHadir} adalah {$maksimalKelompok}."
                );
            }

            return $jumlahManual;
        }

        return $maksimalKelompok;
    }

    /**
     * Simpan ranking dan kelompok ke peserta_ujian.
     */
    public function simpanRankingDanKelompok(int $ujianId, $pesertaHadir, int $jumlahKelompok): void
    {
        DB::transaction(function () use ($ujianId, $pesertaHadir, $jumlahKelompok) {
            $jumlahHadir = $pesertaHadir->count();

            PesertaUjian::where('ujian_id', $ujianId)
                ->where('status_kehadiran', 'tidak_hadir')
                ->update(['ranking' => null, 'kelompok' => null]);

            foreach ($pesertaHadir->values() as $index => $peserta) {
                $ranking = $index + 1;

                if ($ranking <= $jumlahKelompok) {
                    $kelompok = 'atas';
                } elseif ($ranking > ($jumlahHadir - $jumlahKelompok)) {
                    $kelompok = 'bawah';
                } else {
                    $kelompok = 'tengah';
                }

                PesertaUjian::where('id', $peserta->id)->update([
                    'ranking' => $ranking,
                    'kelompok' => $kelompok,
                ]);
            }
        });
    }
}

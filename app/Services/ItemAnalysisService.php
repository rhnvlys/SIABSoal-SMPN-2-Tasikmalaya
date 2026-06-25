<?php

namespace App\Services;

use App\Models\AnalisisButir;
use App\Models\JawabanSiswa;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 3 hanya menghitung Daya Pembeda (DP) dan Tingkat Kesukaran (TK).
 * BA/BB seluruh soal dihitung dalam satu aggregate query, bukan query per soal.
 */
class ItemAnalysisService
{
    public function prosesAnalisis(int $ujianId): array
    {
        $ujian = Ujian::select(['id', 'status'])->findOrFail($ujianId);

        if (!in_array($ujian->status, ['olah_data', 'dianalisis', 'selesai'], true)) {
            throw new \Exception('Olah Data (T2) belum diproses. Silakan proses Olah Data terlebih dahulu.');
        }

        $hasJawaban = JawabanSiswa::whereHas('pesertaUjian', function ($query) use ($ujianId) {
            $query->where('ujian_id', $ujianId);
        })->exists();

        if (!$hasJawaban) {
            throw new \Exception('Jawaban siswa belum tersedia. Proses Data Mentah T1 terlebih dahulu.');
        }

        $groupCounts = PesertaUjian::where('ujian_id', $ujianId)
            ->where('status_kehadiran', 'hadir')
            ->selectRaw("SUM(CASE WHEN kelompok = 'atas' THEN 1 ELSE 0 END) AS ja")
            ->selectRaw("SUM(CASE WHEN kelompok = 'bawah' THEN 1 ELSE 0 END) AS jb")
            ->first();

        $ja = (int) ($groupCounts->ja ?? 0);
        $jb = (int) ($groupCounts->jb ?? 0);

        if ($ja === 0 || $jb === 0) {
            throw new \Exception('Kelompok atas atau bawah belum tersedia. Proses Olah Data terlebih dahulu.');
        }

        if ($ja !== $jb) {
            throw new \Exception("Jumlah kelompok atas dan kelompok bawah harus seimbang. Atas: {$ja}, Bawah: {$jb}");
        }

        $n = $ja + $jb;
        $soalList = Soal::where('ujian_id', $ujianId)
            ->orderBy('nomor_soal')
            ->get(['id', 'ujian_id', 'nomor_soal']);

        $counts = JawabanSiswa::query()
            ->join('peserta_ujian', 'jawaban_siswa.peserta_ujian_id', '=', 'peserta_ujian.id')
            ->where('peserta_ujian.ujian_id', $ujianId)
            ->where('peserta_ujian.status_kehadiran', 'hadir')
            ->whereIn('peserta_ujian.kelompok', ['atas', 'bawah'])
            ->where('jawaban_siswa.is_benar', true)
            ->groupBy('jawaban_siswa.soal_id')
            ->selectRaw('jawaban_siswa.soal_id')
            ->selectRaw("SUM(CASE WHEN peserta_ujian.kelompok = 'atas' THEN 1 ELSE 0 END) AS ba")
            ->selectRaw("SUM(CASE WHEN peserta_ujian.kelompok = 'bawah' THEN 1 ELSE 0 END) AS bb")
            ->get()
            ->keyBy('soal_id');

        $timestamp = now();
        $rows = [];

        foreach ($soalList as $soal) {
            $count = $counts->get($soal->id);
            $ba = (int) ($count->ba ?? 0);
            $bb = (int) ($count->bb ?? 0);
            $dp = $this->hitungDP($ba, $bb, $ja);
            $tk = $this->hitungTK($ba, $bb, $n);
            $kategoriDp = $this->kategoriDP($dp);
            $kategoriTk = $this->kategoriTK($tk);

            $rows[] = [
                'ujian_id' => $ujianId,
                'soal_id' => $soal->id,
                'nomor_soal' => $soal->nomor_soal,
                'ba' => $ba,
                'bb' => $bb,
                'ja' => $ja,
                'jb' => $jb,
                'n_analisis' => $n,
                'jumlah_benar' => $ba + $bb,
                'jumlah_peserta_valid' => $n,
                'dp' => $dp,
                'kategori_dp' => $kategoriDp,
                'tk' => $tk,
                'kategori_tk' => $kategoriTk,
                'keputusan' => $this->tentukanKeputusan($kategoriDp, $kategoriTk),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($ujian, $rows): void {
            AnalisisButir::where('ujian_id', $ujian->id)->delete();

            if ($rows !== []) {
                AnalisisButir::upsert($rows, ['ujian_id', 'soal_id'], [
                    'nomor_soal', 'ba', 'bb', 'ja', 'jb', 'n_analisis', 'jumlah_benar',
                    'jumlah_peserta_valid', 'dp', 'kategori_dp', 'tk', 'kategori_tk',
                    'keputusan', 'updated_at',
                ]);
            }

            $ujian->update(['status' => 'dianalisis']);
        });

        return AnalisisButir::where('ujian_id', $ujianId)->orderBy('nomor_soal')->get()->all();
    }

    public function hitungDP(int $ba, int $bb, int $ja): float
    {
        return $ja === 0 ? 0 : round(($ba - $bb) / $ja, 3);
    }

    public function kategoriDP(float $dp): string
    {
        if ($dp >= 0.40) return 'Baik';
        if ($dp >= 0.21) return 'Revisi';
        return 'Buang';
    }

    public function hitungTK(int $ba, int $bb, int $n): float
    {
        return $n === 0 ? 0 : round(($ba + $bb) / $n, 3);
    }

    public function kategoriTK(float $tk): string
    {
        if ($tk > 0.79) return 'Mudah';
        if ($tk >= 0.30) return 'Sedang';
        return 'Sukar';
    }

    public function tentukanKeputusan(string $kategoriDp, string $kategoriTk): string
    {
        if ($kategoriDp === 'Baik') {
            return $kategoriTk === 'Sedang' ? 'Dipakai' : 'Dipakai dengan catatan';
        }

        return $kategoriDp === 'Revisi' ? 'Revisi' : 'Buang';
    }
}

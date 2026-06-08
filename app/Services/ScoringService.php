<?php

namespace App\Services;

use App\Models\Ujian;
use App\Models\Soal;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\AnalisisButir;
use Illuminate\Support\Facades\DB;

/**
 * ScoringService — Tahap 1 (T1) Data Mentah
 *
 * Mengolah jawaban siswa menjadi skor 1/0
 * dan menghitung nilai akhir peserta ujian.
 */
class ScoringService
{
    /**
     * Validasi apakah kunci jawaban ujian sudah lengkap.
     *
     * @return bool true jika semua soal sudah memiliki kunci jawaban
     */
    public function validasiKunciJawaban(int $ujianId): bool
    {
        $ujian = Ujian::findOrFail($ujianId);
        return $ujian->isKunciLengkap();
    }

    /**
     * Proses jawaban A/B/C/D/E menjadi skor 0/1.
     *
     * Alur:
     * 1. Ambil ujian dan seluruh soal beserta kunci jawaban
     * 2. Validasi kunci jawaban sudah lengkap
     * 3. Untuk setiap peserta ujian yang hadir:
     *    - Bandingkan jawaban siswa dengan kunci jawaban
     *    - Jawaban sama = skor_biner 1, is_benar true
     *    - Jawaban beda/kosong = skor_biner 0, is_benar false
     * 4. Hitung jumlah_benar, jumlah_salah, nilai, keterangan
     * 5. Update status ujian menjadi 'data_mentah'
     */
    public function prosesJawabanABCD(int $ujianId): array
    {
        $ujian = Ujian::with('soal')->findOrFail($ujianId);

        // Validasi kunci jawaban harus lengkap
        if (!$ujian->isKunciLengkap()) {
            throw new \Exception('Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }

        // Buat map kunci jawaban: soal_id => kunci
        $kunciMap = $ujian->soal->pluck('kunci_jawaban', 'id')->toArray();

        $hasilProses = [];

        DB::transaction(function () use ($ujian, $kunciMap, &$hasilProses) {
            $pesertaList = PesertaUjian::where('ujian_id', $ujian->id)->get();

            foreach ($pesertaList as $peserta) {
                if ($peserta->status_kehadiran === 'tidak_hadir') {
                    foreach (array_keys($kunciMap) as $soalId) {
                        JawabanSiswa::updateOrCreate(
                            ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soalId],
                            ['jawaban' => null, 'skor_biner' => 0, 'is_benar' => false]
                        );
                    }

                    // Siswa tidak hadir: nilai 0, keterangan tidak_hadir
                    $peserta->update([
                        'jumlah_benar' => 0,
                        'jumlah_salah' => $ujian->jumlah_soal,
                        'total_skor'   => 0,
                        'nilai'        => 0,
                        'keterangan'   => 'tidak_hadir',
                    ]);
                    continue;
                }

                $jumlahBenar = 0;

                $jawabanList = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
                    ->get()
                    ->keyBy('soal_id');

                foreach ($kunciMap as $soalId => $kunci) {
                    $jawaban = $jawabanList->get($soalId);
                    $jawabanSiswa = $jawaban ? strtoupper(trim((string) $jawaban->jawaban)) : null;
                    $isBenar = ($jawabanSiswa !== null && $jawabanSiswa !== '' && $jawabanSiswa === strtoupper($kunci));
                    $skorBiner = $isBenar ? 1 : 0;

                    JawabanSiswa::updateOrCreate(
                        ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soalId],
                        [
                            'jawaban' => in_array($jawabanSiswa, ['A', 'B', 'C', 'D', 'E']) ? $jawabanSiswa : null,
                            'skor_biner' => $skorBiner,
                            'is_benar' => $isBenar,
                        ]
                    );

                    if ($isBenar) {
                        $jumlahBenar++;
                    }
                }

                $this->hitungNilaiPeserta($peserta, $ujian->jumlah_soal, $jumlahBenar, $ujian->kktp_value);
                $hasilProses[] = $peserta->fresh();
            }

            PesertaUjian::where('ujian_id', $ujian->id)->update([
                'ranking' => null,
                'kelompok' => null,
            ]);

            AnalisisButir::where('ujian_id', $ujian->id)->delete();
            $ujian->update(['status' => 'data_mentah']);
        });

        return $hasilProses;
    }

    /**
     * Proses skor 0/1 langsung (tanpa konversi dari A/B/C/D/E).
     *
     * Alur:
     * 1. Validasi jumlah kolom skor sesuai jumlah_soal
     * 2. Untuk setiap peserta:
     *    - skor_biner langsung dari data
     *    - is_benar = true jika skor_biner = 1
     * 3. Hitung jumlah_benar, jumlah_salah, nilai, keterangan
     */
    public function prosesSkorBiner(int $ujianId): array
    {
        $ujian = Ujian::with('soal')->findOrFail($ujianId);

        if (!$ujian->isKunciLengkap()) {
            throw new \Exception('Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }

        $hasilProses = [];

        DB::transaction(function () use ($ujian, &$hasilProses) {
            $pesertaList = PesertaUjian::where('ujian_id', $ujian->id)->get();
            $soalIds = $ujian->soal->pluck('id');

            foreach ($pesertaList as $peserta) {
                if ($peserta->status_kehadiran === 'tidak_hadir') {
                    foreach ($soalIds as $soalId) {
                        JawabanSiswa::updateOrCreate(
                            ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soalId],
                            ['jawaban' => null, 'skor_biner' => 0, 'is_benar' => false]
                        );
                    }

                    $peserta->update([
                        'jumlah_benar' => 0,
                        'jumlah_salah' => $ujian->jumlah_soal,
                        'total_skor'   => 0,
                        'nilai'        => 0,
                        'keterangan'   => 'tidak_hadir',
                    ]);
                    continue;
                }

                $jumlahBenar = 0;

                foreach ($soalIds as $soalId) {
                    $jawaban = JawabanSiswa::firstOrCreate(
                        ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soalId],
                        ['jawaban' => null, 'skor_biner' => 0, 'is_benar' => false]
                    );

                    $skorBiner = (int) $jawaban->skor_biner === 1 ? 1 : 0;

                    $jawaban->update([
                        'jawaban' => null,
                        'skor_biner' => $skorBiner,
                        'is_benar' => $skorBiner === 1,
                    ]);

                    $jumlahBenar += $skorBiner;
                }

                $this->hitungNilaiPeserta($peserta, $ujian->jumlah_soal, $jumlahBenar, $ujian->kktp_value);
                $hasilProses[] = $peserta->fresh();
            }

            PesertaUjian::where('ujian_id', $ujian->id)->update([
                'ranking' => null,
                'kelompok' => null,
            ]);

            AnalisisButir::where('ujian_id', $ujian->id)->delete();
            $ujian->update(['status' => 'data_mentah']);
        });

        return $hasilProses;
    }

    /**
     * Hitung nilai akhir peserta ujian.
     *
     * Rumus:
     *   Nilai = (jumlah_benar / jumlah_soal) × 100
     *
     * Keterangan:
     *   - Nilai >= KKM → tercapai
     *   - Nilai < KKM  → perlu_peningkatan
     */
    public function hitungNilaiPeserta(PesertaUjian $peserta, int $jumlahSoal, int $jumlahBenar, float $kkm): void
    {
        $jumlahSalah = $jumlahSoal - $jumlahBenar;
        $totalSkor   = $jumlahBenar; // Bobot default 1 per soal
        $nilai       = $jumlahSoal > 0 ? round(($jumlahBenar / $jumlahSoal) * 100, 2) : 0;
        $keterangan  = $nilai >= $kkm ? 'tercapai' : 'perlu_peningkatan';

        $peserta->update([
            'jumlah_benar' => $jumlahBenar,
            'jumlah_salah' => $jumlahSalah,
            'total_skor'   => $totalSkor,
            'nilai'        => $nilai,
            'keterangan'   => $keterangan,
        ]);
    }
}

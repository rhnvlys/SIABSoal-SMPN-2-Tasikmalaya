<?php

namespace App\Services;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\AnalisisButir;
use Illuminate\Support\Facades\DB;

/**
 * ScoringService — Tahap 1 (T1) Data Mentah
 *
 * Mengolah jawaban siswa menjadi skor 1/0 dan menghitung nilai akhir peserta ujian.
 * Implementasi dibuat bulk/upsert agar proses T1 tidak timeout di Vercel Serverless.
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
     */
    public function prosesJawabanABCD(int $ujianId): array
    {
        $ujian = Ujian::with('soal')->findOrFail($ujianId);

        if (!$ujian->isKunciLengkap()) {
            throw new \Exception('Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }

        $soalList = $ujian->soal->sortBy('nomor_soal')->values();
        $soalIds = $soalList->pluck('id')->values();
        $kunciMap = $soalList->pluck('kunci_jawaban', 'id')->map(fn ($value) => strtoupper((string) $value))->toArray();

        $hasilProses = [];

        DB::transaction(function () use ($ujian, $soalIds, $kunciMap, &$hasilProses) {
            $pesertaList = PesertaUjian::where('ujian_id', $ujian->id)->get();
            $pesertaIds = $pesertaList->pluck('id')->values();

            if ($pesertaList->isEmpty() || empty($kunciMap)) {
                $ujian->update(['status' => 'data_mentah']);
                return;
            }

            $jawabanMap = JawabanSiswa::whereIn('peserta_ujian_id', $pesertaIds)
                ->whereIn('soal_id', $soalIds)
                ->get(['peserta_ujian_id', 'soal_id', 'jawaban'])
                ->groupBy('peserta_ujian_id')
                ->map(fn ($rows) => $rows->keyBy('soal_id'));

            $timestamp = now();
            $jawabanRows = [];
            $pesertaUpdates = [];

            foreach ($pesertaList as $peserta) {
                $jumlahBenar = 0;

                foreach ($kunciMap as $soalId => $kunci) {
                    if ($peserta->status_kehadiran === 'tidak_hadir') {
                        $jawabanSiswa = null;
                        $isBenar = false;
                    } else {
                        $jawabanRecord = $jawabanMap->get($peserta->id)?->get($soalId);
                        $rawJawaban = $jawabanRecord ? strtoupper(trim((string) $jawabanRecord->jawaban)) : '';
                        $jawabanSiswa = in_array($rawJawaban, ['A', 'B', 'C', 'D', 'E'], true) ? $rawJawaban : null;
                        $isBenar = $jawabanSiswa !== null && $jawabanSiswa === strtoupper((string) $kunci);
                    }

                    $skorBiner = $isBenar ? 1 : 0;
                    $jumlahBenar += $skorBiner;

                    $jawabanRows[] = [
                        'peserta_ujian_id' => $peserta->id,
                        'soal_id' => $soalId,
                        'jawaban' => $jawabanSiswa,
                        'skor_biner' => $skorBiner,
                        'is_benar' => $isBenar,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                $pesertaUpdates[] = $this->buildPesertaUpdateRow($peserta, $ujian->jumlah_soal, $jumlahBenar, $ujian->kktp_value, $timestamp);
            }

            $this->bulkUpsertJawaban($jawabanRows);
            $this->bulkUpdatePeserta($pesertaUpdates);

            PesertaUjian::where('ujian_id', $ujian->id)->update([
                'ranking' => null,
                'kelompok' => null,
                'updated_at' => $timestamp,
            ]);

            AnalisisButir::where('ujian_id', $ujian->id)->delete();
            $ujian->update(['status' => 'data_mentah']);

            $hasilProses = PesertaUjian::where('ujian_id', $ujian->id)->get()->all();
        });

        return $hasilProses;
    }

    /**
     * Proses skor 0/1 langsung tanpa konversi dari A/B/C/D/E.
     */
    public function prosesSkorBiner(int $ujianId): array
    {
        $ujian = Ujian::with('soal')->findOrFail($ujianId);

        if (!$ujian->isKunciLengkap()) {
            throw new \Exception('Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }

        $soalIds = $ujian->soal->sortBy('nomor_soal')->pluck('id')->values();
        $hasilProses = [];

        DB::transaction(function () use ($ujian, $soalIds, &$hasilProses) {
            $pesertaList = PesertaUjian::where('ujian_id', $ujian->id)->get();
            $pesertaIds = $pesertaList->pluck('id')->values();

            if ($pesertaList->isEmpty() || $soalIds->isEmpty()) {
                $ujian->update(['status' => 'data_mentah']);
                return;
            }

            $skorMap = JawabanSiswa::whereIn('peserta_ujian_id', $pesertaIds)
                ->whereIn('soal_id', $soalIds)
                ->get(['peserta_ujian_id', 'soal_id', 'skor_biner'])
                ->groupBy('peserta_ujian_id')
                ->map(fn ($rows) => $rows->keyBy('soal_id'));

            $timestamp = now();
            $jawabanRows = [];
            $pesertaUpdates = [];

            foreach ($pesertaList as $peserta) {
                $jumlahBenar = 0;

                foreach ($soalIds as $soalId) {
                    $skorBiner = 0;

                    if ($peserta->status_kehadiran !== 'tidak_hadir') {
                        $jawabanRecord = $skorMap->get($peserta->id)?->get($soalId);
                        $skorBiner = ((int) ($jawabanRecord?->skor_biner ?? 0)) === 1 ? 1 : 0;
                    }

                    $jumlahBenar += $skorBiner;

                    $jawabanRows[] = [
                        'peserta_ujian_id' => $peserta->id,
                        'soal_id' => $soalId,
                        'jawaban' => null,
                        'skor_biner' => $skorBiner,
                        'is_benar' => $skorBiner === 1,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                $pesertaUpdates[] = $this->buildPesertaUpdateRow($peserta, $ujian->jumlah_soal, $jumlahBenar, $ujian->kktp_value, $timestamp);
            }

            $this->bulkUpsertJawaban($jawabanRows);
            $this->bulkUpdatePeserta($pesertaUpdates);

            PesertaUjian::where('ujian_id', $ujian->id)->update([
                'ranking' => null,
                'kelompok' => null,
                'updated_at' => $timestamp,
            ]);

            AnalisisButir::where('ujian_id', $ujian->id)->delete();
            $ujian->update(['status' => 'data_mentah']);

            $hasilProses = PesertaUjian::where('ujian_id', $ujian->id)->get()->all();
        });

        return $hasilProses;
    }

    /**
     * Hitung nilai akhir peserta ujian.
     */
    public function hitungNilaiPeserta(PesertaUjian $peserta, int $jumlahSoal, int $jumlahBenar, float $kkm): void
    {
        $peserta->update($this->buildPesertaScoreData($peserta, $jumlahSoal, $jumlahBenar, $kkm));
    }

    private function buildPesertaUpdateRow(PesertaUjian $peserta, int $jumlahSoal, int $jumlahBenar, float $kkm, $timestamp): array
    {
        return array_merge(
            ['id' => $peserta->id, 'updated_at' => $timestamp],
            $this->buildPesertaScoreData($peserta, $jumlahSoal, $jumlahBenar, $kkm)
        );
    }

    private function buildPesertaScoreData(PesertaUjian $peserta, int $jumlahSoal, int $jumlahBenar, float $kkm): array
    {
        if ($peserta->status_kehadiran === 'tidak_hadir') {
            return [
                'jumlah_benar' => 0,
                'jumlah_salah' => $jumlahSoal,
                'total_skor' => 0,
                'nilai' => 0,
                'keterangan' => 'tidak_hadir',
            ];
        }

        $jumlahSalah = max(0, $jumlahSoal - $jumlahBenar);
        $nilai = $jumlahSoal > 0 ? round(($jumlahBenar / $jumlahSoal) * 100, 2) : 0;

        return [
            'jumlah_benar' => $jumlahBenar,
            'jumlah_salah' => $jumlahSalah,
            'total_skor' => $jumlahBenar,
            'nilai' => $nilai,
            'keterangan' => $nilai >= $kkm ? 'tercapai' : 'perlu_peningkatan',
        ];
    }

    private function bulkUpsertJawaban(array $rows): void
    {
        foreach (array_chunk($rows, 1000) as $chunk) {
            JawabanSiswa::upsert(
                $chunk,
                ['peserta_ujian_id', 'soal_id'],
                ['jawaban', 'skor_biner', 'is_benar', 'updated_at']
            );
        }
    }

    private function bulkUpdatePeserta(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            PesertaUjian::upsert(
                $chunk,
                ['id'],
                ['jumlah_benar', 'jumlah_salah', 'total_skor', 'nilai', 'keterangan', 'updated_at']
            );
        }
    }
}

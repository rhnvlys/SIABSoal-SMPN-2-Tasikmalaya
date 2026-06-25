<?php

namespace App\Services;

use App\Models\AnalisisButir;
use App\Models\JawabanSiswa;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 1 Data Mentah.
 *
 * Request web memanggil prepareBatch() sekali lalu processBatch() berulang.
 * Setiap transaction hanya menangani maksimal sepuluh peserta agar aman untuk
 * batas durasi Vercel Serverless.
 */
class ScoringService
{
    public const DEFAULT_BATCH_SIZE = 5;
    public const MAX_BATCH_SIZE = 10;

    public function validasiKunciJawaban(int $ujianId): bool
    {
        return Ujian::findOrFail($ujianId)->isKunciLengkap();
    }

    /**
     * Reset hasil turunan T2/T3 sebelum batch pertama dijalankan.
     */
    public function prepareBatch(int $ujianId): int
    {
        $ujian = Ujian::select(['id', 'jumlah_soal', 'status'])->findOrFail($ujianId);
        $this->assertQuestionKeysComplete($ujian);

        $total = PesertaUjian::where('ujian_id', $ujian->id)->count();

        DB::transaction(function () use ($ujian, $total): void {
            PesertaUjian::where('ujian_id', $ujian->id)->update([
                'ranking' => null,
                'kelompok' => null,
                'updated_at' => now(),
            ]);

            AnalisisButir::where('ujian_id', $ujian->id)->delete();
            $ujian->update(['status' => $total === 0 ? 'data_mentah' : 'kunci_lengkap']);
        });

        return $total;
    }

    /**
     * Proses satu potongan peserta dan kembalikan progress keseluruhan ujian.
     */
    public function processBatch(
        int $ujianId,
        string $mode,
        int $offset,
        int $limit = self::DEFAULT_BATCH_SIZE
    ): array {
        if (!in_array($mode, ['abcd', 'biner'], true)) {
            throw new \InvalidArgumentException('Mode proses T1 tidak valid.');
        }

        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset batch tidak boleh negatif.');
        }

        $limit = max(1, min($limit, self::MAX_BATCH_SIZE));
        $ujian = Ujian::select(['id', 'jumlah_soal', 'kkm', 'kktp', 'status'])->findOrFail($ujianId);
        $soalList = $this->questionList($ujian);
        $total = PesertaUjian::where('ujian_id', $ujian->id)->count();

        if ($offset > $total) {
            throw new \InvalidArgumentException('Offset batch melebihi jumlah peserta. Mulai ulang proses T1.');
        }

        if ($total === 0) {
            $ujian->update(['status' => 'data_mentah']);

            return $this->progressResult(0, 0, 0);
        }

        return DB::transaction(function () use ($ujian, $soalList, $mode, $offset, $limit, $total): array {
            $pesertaList = PesertaUjian::where('ujian_id', $ujian->id)
                ->orderBy('id')
                ->offset($offset)
                ->limit($limit)
                ->get([
                    'id',
                    'ujian_id',
                    'siswa_id',
                    'kelas_id',
                    'status_kehadiran',
                    'created_at',
                ]);

            if ($pesertaList->isEmpty()) {
                throw new \RuntimeException('Peserta untuk batch berikutnya tidak ditemukan. Mulai ulang proses T1.');
            }

            $pesertaIds = $pesertaList->pluck('id');
            $soalIds = $soalList->pluck('id');
            $jawabanMap = JawabanSiswa::whereIn('peserta_ujian_id', $pesertaIds)
                ->whereIn('soal_id', $soalIds)
                ->get(['peserta_ujian_id', 'soal_id', 'jawaban', 'skor_biner'])
                ->groupBy('peserta_ujian_id')
                ->map(fn (Collection $rows) => $rows->keyBy('soal_id'));

            $timestamp = now();
            $jawabanRows = [];
            $pesertaRows = [];

            foreach ($pesertaList as $peserta) {
                $jumlahBenar = 0;
                $jawabanPeserta = $jawabanMap->get($peserta->id, collect());

                foreach ($soalList as $soal) {
                    $existing = $jawabanPeserta->get($soal->id);
                    [$jawaban, $skorBiner] = $this->scoreAnswer($peserta, $soal, $existing, $mode);
                    $jumlahBenar += $skorBiner;

                    $jawabanRows[] = [
                        'peserta_ujian_id' => $peserta->id,
                        'soal_id' => $soal->id,
                        'jawaban' => $jawaban,
                        'skor_biner' => $skorBiner,
                        'is_benar' => $skorBiner === 1,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                $pesertaRows[] = array_merge([
                    'id' => $peserta->id,
                    'ujian_id' => $peserta->ujian_id,
                    'siswa_id' => $peserta->siswa_id,
                    'kelas_id' => $peserta->kelas_id,
                    'status_kehadiran' => $peserta->status_kehadiran,
                    'created_at' => $peserta->created_at ?? $timestamp,
                    'updated_at' => $timestamp,
                ], $this->buildPesertaScoreData(
                    $peserta,
                    (int) $ujian->jumlah_soal,
                    $jumlahBenar,
                    (float) $ujian->kktp_value
                ));
            }

            $this->bulkUpsertJawaban($jawabanRows);
            $this->bulkUpdatePeserta($pesertaRows);

            $processed = min($total, $offset + $pesertaList->count());
            if ($processed >= $total) {
                $ujian->update(['status' => 'data_mentah']);
            }

            return $this->progressResult($total, $processed, $processed);
        });
    }

    /**
     * Compatibility API untuk pemanggilan CLI lama. Route web tidak memakai
     * method ini karena seluruh batch tidak boleh dijalankan dalam satu request.
     */
    public function prosesJawabanABCD(int $ujianId): array
    {
        return $this->processAllForCompatibility($ujianId, 'abcd');
    }

    public function prosesSkorBiner(int $ujianId): array
    {
        return $this->processAllForCompatibility($ujianId, 'biner');
    }

    public function hitungNilaiPeserta(PesertaUjian $peserta, int $jumlahSoal, int $jumlahBenar, float $kkm): void
    {
        $peserta->update($this->buildPesertaScoreData($peserta, $jumlahSoal, $jumlahBenar, $kkm));
    }

    private function processAllForCompatibility(int $ujianId, string $mode): array
    {
        $total = $this->prepareBatch($ujianId);

        for ($offset = 0; $offset < $total; $offset += self::DEFAULT_BATCH_SIZE) {
            $this->processBatch($ujianId, $mode, $offset, self::DEFAULT_BATCH_SIZE);
        }

        return PesertaUjian::where('ujian_id', $ujianId)->orderBy('id')->get()->all();
    }

    private function assertQuestionKeysComplete(Ujian $ujian): void
    {
        $complete = Soal::where('ujian_id', $ujian->id)
            ->whereNotNull('kunci_jawaban')
            ->count();

        if ($complete !== (int) $ujian->jumlah_soal) {
            throw new \RuntimeException('Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }
    }

    private function questionList(Ujian $ujian): Collection
    {
        $soalList = Soal::where('ujian_id', $ujian->id)
            ->orderBy('nomor_soal')
            ->get(['id', 'ujian_id', 'nomor_soal', 'kunci_jawaban']);

        if ($soalList->count() !== (int) $ujian->jumlah_soal || $soalList->contains(fn (Soal $soal) => !$soal->kunci_jawaban)) {
            throw new \RuntimeException('Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }

        return $soalList;
    }

    private function scoreAnswer(PesertaUjian $peserta, Soal $soal, ?JawabanSiswa $existing, string $mode): array
    {
        if ($peserta->status_kehadiran === 'tidak_hadir') {
            return [null, 0];
        }

        if ($mode === 'biner') {
            return [null, ((int) ($existing?->skor_biner ?? 0)) === 1 ? 1 : 0];
        }

        $raw = strtoupper(trim((string) ($existing?->jawaban ?? '')));
        $jawaban = in_array($raw, ['A', 'B', 'C', 'D', 'E'], true) ? $raw : null;
        $isBenar = $jawaban !== null && $jawaban === strtoupper((string) $soal->kunci_jawaban);

        return [$jawaban, $isBenar ? 1 : 0];
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

        $nilai = $jumlahSoal > 0 ? round(($jumlahBenar / $jumlahSoal) * 100, 2) : 0;

        return [
            'jumlah_benar' => $jumlahBenar,
            'jumlah_salah' => max(0, $jumlahSoal - $jumlahBenar),
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
        foreach (array_chunk($rows, self::MAX_BATCH_SIZE) as $chunk) {
            PesertaUjian::upsert(
                $chunk,
                ['id'],
                ['jumlah_benar', 'jumlah_salah', 'total_skor', 'nilai', 'keterangan', 'updated_at']
            );
        }
    }

    private function progressResult(int $total, int $processed, int $nextOffset): array
    {
        $remaining = max(0, $total - $processed);

        return [
            'status' => $remaining === 0 ? 'completed' : 'running',
            'total' => $total,
            'processed' => $processed,
            'remaining' => $remaining,
            'progress' => $total > 0 ? round(($processed / $total) * 100, 2) : 100,
            'next_offset' => $nextOffset,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Ujian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use Illuminate\Support\Facades\DB;

/**
 * ImportService — Validasi dan import data jawaban siswa.
 *
 * Mendukung dua mode:
 *   - abcd: Jawaban A/B/C/D/E
 *   - biner: Skor langsung 0/1
 *
 * Template kolom:
 *   nis, nisn, nama_siswa, jenis_kelamin, status_kehadiran, soal_1...soal_n
 */
class ImportService
{
    public function expectedHeader(Ujian $ujian, string $mode): array
    {
        $ujian->loadMissing('soal');

        $prefix = $mode === 'biner' ? 'skor_' : 'soal_';
        $header = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];

        foreach ($ujian->soal->sortBy('nomor_soal') as $soal) {
            $header[] = $prefix . $soal->nomor_soal;
        }

        return $header;
    }

    public function normalizeHeader(array $header): array
    {
        return array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
            return strtolower(trim($value));
        }, $header);
    }

    public function isHeaderValid(array $header, Ujian $ujian, string $mode): bool
    {
        return $this->normalizeHeader($header) === $this->expectedHeader($ujian, $mode);
    }

    /**
     * Validasi data import dan kembalikan hasil preview.
     *
     * @return array ['valid' => [...], 'errors' => [...], 'summary' => [...]]
     */
    public function preview(array $rows, Ujian $ujian, string $mode): array
    {
        $ujian->loadMissing(['soal', 'kelas']);
        $soalList = $ujian->soal->sortBy('nomor_soal')->values();
        $jumlahSoal = $soalList->count();
        $kelasUjianIds = $ujian->kelas->pluck('id')->all();

        $validRows = [];
        $errors = [];
        $nisTracker = [];

        foreach ($rows as $rowIndex => $row) {
            $lineNum = $rowIndex + 2; // +2 karena header row 1
            $nis  = trim($row[0] ?? '');
            $nisn = trim($row[1] ?? '');
            $nama = trim($row[2] ?? '');
            $jk   = strtoupper(trim($row[3] ?? ''));
            $statusHadir = str_replace([' ', '-'], '_', strtolower(trim($row[4] ?? 'hadir')));

            $rowErrors = [];

            // F7a: NIS kosong
            if (empty($nis)) {
                $rowErrors[] = "NIS kosong";
            }

            // F7b: NIS tidak ditemukan (cek di DB)
            $siswa = null;
            $siswaKelas = null;
            if (!empty($nis)) {
                $siswa = Siswa::where('nis', $nis)->first();
                if (!$siswa) {
                    $rowErrors[] = "NIS '{$nis}' tidak ditemukan di database";
                } else {
                    $siswaKelas = SiswaKelas::where('siswa_id', $siswa->id)
                        ->whereIn('kelas_id', $kelasUjianIds)
                        ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
                        ->where('status', 'aktif')
                        ->orderBy('id')
                        ->first();

                    if (!$siswaKelas) {
                        $rowErrors[] = "NIS '{$nis}' tidak terdaftar pada kelas ujian";
                    }
                }
            }

            // F7c: Duplikasi NIS dalam file
            if (!empty($nis)) {
                if (isset($nisTracker[$nis])) {
                    $rowErrors[] = "NIS '{$nis}' duplikat (sudah ada di baris {$nisTracker[$nis]})";
                } else {
                    $nisTracker[$nis] = $lineNum;
                }
            }

            // F7d: Jumlah kolom soal tidak sesuai
            $jawabanCols = array_slice($row, 5);
            if (count($jawabanCols) !== $jumlahSoal) {
                $rowErrors[] = "Jumlah kolom jawaban tidak sesuai (butuh {$jumlahSoal}, ditemukan " . count($jawabanCols) . ")";
            }

            if (!in_array($statusHadir, ['hadir', 'tidak_hadir'])) {
                $rowErrors[] = "Status kehadiran harus hadir atau tidak_hadir";
            }

            // F7e/f: Validasi isi jawaban per soal
            foreach ($soalList as $idx => $soal) {
                $val = strtoupper(preg_replace('/\s+/', '', (string) ($jawabanCols[$idx] ?? '')));
                if ($val === '') continue; // kosong dibolehkan

                if ($mode === 'abcd') {
                    if (!in_array($val, ['A','B','C','D','E'])) {
                        $rowErrors[] = "Soal " . ($idx+1) . ": jawaban '{$val}' bukan A/B/C/D/E";
                    }
                } else {
                    if (!in_array($val, ['0','1'])) {
                        $rowErrors[] = "Soal " . ($idx+1) . ": skor '{$val}' bukan 0/1";
                    }
                }
            }

            if (count($rowErrors) > 0) {
                $errors[] = [
                    'baris' => $lineNum,
                    'nis' => $nis,
                    'nama' => $nama ?: ($siswa->nama_siswa ?? ''),
                    'errors' => $rowErrors,
                ];
            } else {
                $validRows[] = [
                    'row_index' => $rowIndex,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'nama' => $siswa->nama_siswa ?? $nama,
                    'jenis_kelamin' => in_array($jk, ['L','P']) ? $jk : ($siswa->jenis_kelamin ?? 'L'),
                    'status_kehadiran' => $statusHadir,
                    'siswa_id' => $siswa->id ?? null,
                    'kelas_id' => $siswaKelas->kelas_id ?? null,
                    'jawaban' => array_map(fn ($value) => strtoupper(preg_replace('/\s+/', '', (string) $value)), array_slice($jawabanCols, 0, $jumlahSoal)),
                ];
            }
        }

        return [
            'valid' => $validRows,
            'errors' => $errors,
            'summary' => [
                'total' => count($rows),
                'valid_count' => count($validRows),
                'error_count' => count($errors),
            ],
        ];
    }

    /**
     * Import data yang sudah divalidasi ke database.
     * Menggunakan DB::transaction (F8).
     */
    public function importValidated(array $rows, Ujian $ujian, string $mode): int
    {
        $ujian->loadMissing('soal');
        $soalList = $ujian->soal->sortBy('nomor_soal')->values();
        $imported = 0;

        DB::transaction(function () use ($rows, $ujian, $soalList, $mode, &$imported) {
            foreach ($rows as $row) {
                if (!isset($row['siswa_id'], $row['kelas_id'], $row['jawaban'])) {
                    continue;
                }

                // Buat/update peserta ujian
                $peserta = PesertaUjian::updateOrCreate(
                    ['ujian_id' => $ujian->id, 'siswa_id' => $row['siswa_id']],
                    [
                        'kelas_id' => $row['kelas_id'],
                        'status_kehadiran' => $row['status_kehadiran'],
                        'jumlah_benar' => 0,
                        'jumlah_salah' => 0,
                        'total_skor' => 0,
                        'nilai' => 0,
                    ]
                );

                // Simpan jawaban per soal
                foreach ($soalList as $idx => $soal) {
                    $jawabanRaw = strtoupper(trim($row['jawaban'][$idx] ?? ''));

                    if ($mode === 'abcd') {
                        $jawaban = in_array($jawabanRaw, ['A','B','C','D','E']) ? $jawabanRaw : null;
                        JawabanSiswa::updateOrCreate(
                            ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soal->id],
                            ['jawaban' => $jawaban, 'skor_biner' => 0, 'is_benar' => false]
                        );
                    } else {
                        $skorBiner = in_array($jawabanRaw, ['1','0']) ? (int)$jawabanRaw : 0;
                        JawabanSiswa::updateOrCreate(
                            ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soal->id],
                            ['jawaban' => null, 'skor_biner' => $skorBiner, 'is_benar' => (bool)$skorBiner]
                        );
                    }
                }

                $imported++;
            }
        });

        return $imported;
    }
}

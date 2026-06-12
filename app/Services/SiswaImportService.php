<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\SiswaKelas;
use Illuminate\Support\Facades\DB;

class SiswaImportService
{
    public function expectedHeader(): array
    {
        return ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'];
    }

    public function normalizeHeader(array $header): array
    {
        return array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
            return strtolower(trim($value));
        }, $header);
    }

    public function isHeaderValid(array $header): bool
    {
        return $this->normalizeHeader($header) === $this->expectedHeader();
    }

    /**
     * Preview student import rows.
     */
    public function preview(array $rows): array
    {
        $validRows = [];
        $errors = [];
        $nisTracker = [];

        foreach ($rows as $rowIndex => $row) {
            $lineNum = $rowIndex + 2; // Header is row 1
            $nis          = trim($row[0] ?? '');
            $nisn         = trim($row[1] ?? '');
            $namaSiswa    = trim($row[2] ?? '');
            $jenisKelamin = strtoupper(trim($row[3] ?? ''));
            $kelasName    = trim($row[4] ?? '');
            $tahunAjaranName = trim($row[5] ?? '');
            $status       = strtolower(trim($row[6] ?? 'aktif'));

            $rowErrors = [];
            $displayName = $namaSiswa ?: ($nis ?: "Baris {$lineNum}");

            if (empty($nis)) {
                $rowErrors[] = "Baris {$lineNum}: NIS wajib diisi.";
            }

            if (empty($namaSiswa)) {
                $rowErrors[] = "Baris {$lineNum}: Nama siswa wajib diisi.";
            }

            if (!in_array($jenisKelamin, ['L', 'P'])) {
                $rowErrors[] = "Baris {$lineNum}, {$displayName}: Jenis kelamin harus L atau P.";
            }

            if (!in_array($status, ['aktif', 'nonaktif', 'lulus'])) {
                $rowErrors[] = "Baris {$lineNum}, {$displayName}: Status harus aktif, nonaktif, atau lulus.";
            }

            // Check duplicate NIS in file
            if (!empty($nis)) {
                if (isset($nisTracker[$nis])) {
                    $rowErrors[] = "Baris {$lineNum}: NIS {$nis} duplikat dengan baris {$nisTracker[$nis]} di dalam file.";
                } else {
                    $nisTracker[$nis] = $lineNum;
                }
            }

            // Validate Tahun Ajaran
            $tahunAjaran = null;
            if (!empty($tahunAjaranName)) {
                // Find matching tahun ajaran. If multiple, prioritize active one.
                $tahunAjaran = TahunAjaran::where('tahun_ajaran', $tahunAjaranName)
                    ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
                    ->first();

                if (!$tahunAjaran) {
                    $rowErrors[] = "Baris {$lineNum}, {$displayName}: Tahun Ajaran '{$tahunAjaranName}' tidak ditemukan di database.";
                }
            } else {
                $rowErrors[] = "Baris {$lineNum}, {$displayName}: Tahun Ajaran wajib diisi.";
            }

            // Validate Kelas
            $kelas = null;
            if ($tahunAjaran && !empty($kelasName)) {
                $kelas = Kelas::where('nama_kelas', $kelasName)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->first();

                if (!$kelas) {
                    $rowErrors[] = "Baris {$lineNum}, {$displayName}: Kelas '{$kelasName}' tidak ditemukan untuk Tahun Ajaran '{$tahunAjaranName}' di database.";
                }
            } elseif (empty($kelasName)) {
                $rowErrors[] = "Baris {$lineNum}, {$displayName}: Kelas wajib diisi.";
            }

            if (count($rowErrors) > 0) {
                $errors[] = [
                    'baris' => $lineNum,
                    'nis' => $nis,
                    'nama' => $namaSiswa,
                    'errors' => $rowErrors,
                ];
            } else {
                $validRows[] = [
                    'row_index' => $rowIndex,
                    'nis' => $nis,
                    'nisn' => $nisn ?: null,
                    'nama_siswa' => $namaSiswa,
                    'jenis_kelamin' => $jenisKelamin,
                    'status' => $status,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'nama_kelas' => $kelasName,
                    'nama_tahun_ajaran' => $tahunAjaranName,
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
     * Import validated students list.
     */
    public function importValidated(array $rows): int
    {
        $imported = 0;

        DB::transaction(function () use ($rows, &$imported) {
            foreach ($rows as $row) {
                // 1. Create or update Student details
                $siswa = Siswa::updateOrCreate(
                    ['nis' => $row['nis']],
                    [
                        'nisn' => $row['nisn'],
                        'nama_siswa' => $row['nama_siswa'],
                        'jenis_kelamin' => $row['jenis_kelamin'],
                        'status' => $row['status'],
                    ]
                );

                // 2. Link to Kelas for this Tahun Ajaran
                SiswaKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $row['tahun_ajaran_id'],
                    ],
                    [
                        'kelas_id' => $row['kelas_id'],
                        'status' => 'aktif', // default link status is aktif
                    ]
                );

                $imported++;
            }
        });

        return $imported;
    }
}

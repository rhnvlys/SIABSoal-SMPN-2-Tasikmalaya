<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompleteTemplateImportService
{
    public const PROCESSABLE_SHEETS = [
        'DATA_KELAS',
        'DATA_SISWA',
        'TP_LM_KKTP',
        'DAFTAR_HADIR',
        'KUNCI_JAWABAN',
        'INPUT_JAWABAN_ABCD',
        'INPUT_SKOR_01',
        'DATA_IMPORT_SYSTEM',
        'DATA_INPUT',
    ];

    public function __construct(private readonly ImportService $importService)
    {
    }

    public function preview(UploadedFile $file, User $user, Ujian $ujian): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetNames = array_map(fn ($name) => strtoupper(trim((string) $name)), $spreadsheet->getSheetNames());
        $sheets = $this->spreadsheetRows($spreadsheet);

        $result = [
            'file_name' => $file->getClientOriginalName(),
            'found_sheets' => $sheetNames,
            'valid_sheets' => [],
            'processable_sheets' => [],
            'ignored_sheets' => [],
            'row_counts' => [],
            'errors' => [],
            'warnings' => [],
            'payload' => [],
            'summary' => [
                'processed_sheet_count' => 0,
                'ignored_sheet_count' => 0,
                'error_count' => 0,
                'warning_count' => 0,
            ],
        ];

        foreach ($sheetNames as $idx => $sheetName) {
            if (!in_array($sheetName, self::PROCESSABLE_SHEETS, true)) {
                continue;
            }

            $rows = $this->filledRows($sheets[$idx] ?? []);
            $result['row_counts'][$sheetName] = max(count($rows) - 1, 0);
            $result['valid_sheets'][] = $sheetName;

            if (!$this->canProcessSheet($sheetName, $user)) {
                $result['ignored_sheets'][$sheetName] = $this->ignoreReasonForSheet($sheetName, $user, $ujian);
                continue;
            }

            $sheetPayload = $this->previewSheet($sheetName, $rows, $user, $ujian, $result);
            if (count($sheetPayload) > 0) {
                $targetSheet = $this->payloadTargetSheet($sheetName, $rows, $ujian);
                $result['payload'][$targetSheet] = array_merge($result['payload'][$targetSheet] ?? [], $sheetPayload);
                $result['processable_sheets'][] = $sheetName;
            }
        }

        if (empty($result['payload'])) {
            $result['errors'][] = 'Template tidak valid. Silakan gunakan template resmi dari SIABSoal.';
        }

        $result['summary']['processed_sheet_count'] = count($result['processable_sheets']);
        $result['summary']['ignored_sheet_count'] = count($result['ignored_sheets']);
        $result['summary']['error_count'] = count($result['errors']);
        $result['summary']['warning_count'] = count($result['warnings']);

        return $result;
    }

    private function spreadsheetRows(Spreadsheet $spreadsheet): array
    {
        return array_map(
            fn (Worksheet $worksheet) => $this->worksheetRows($worksheet),
            $spreadsheet->getAllSheets()
        );
    }

    private function worksheetRows(Worksheet $worksheet): array
    {
        $highestRow = $worksheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
        $rows = [];

        for ($row = 1; $row <= $highestRow; $row++) {
            $values = [];

            for ($column = 1; $column <= $highestColumn; $column++) {
                $values[] = $this->cellRawValue($worksheet, $column, $row);
            }

            $rows[] = $values;
        }

        return $rows;
    }

    private function cellRawValue(Worksheet $worksheet, int $column, int $row): mixed
    {
        $cell = $worksheet->getCellByColumnAndRow($column, $row);
        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            $cachedValue = method_exists($cell, 'getOldCalculatedValue')
                ? $cell->getOldCalculatedValue()
                : null;

            return $cachedValue ?? '';
        }

        if ($value instanceof RichText) {
            return $value->getPlainText();
        }

        return $value;
    }

    public function import(array $payload, User $user, Ujian $ujian): array
    {
        $counts = [
            'DATA_KELAS' => 0,
            'DATA_SISWA' => 0,
            'TP_LM_KKTP' => 0,
            'DAFTAR_HADIR' => 0,
            'KUNCI_JAWABAN' => 0,
            'INPUT_JAWABAN_ABCD' => 0,
            'INPUT_SKOR_01' => 0,
        ];

        DB::transaction(function () use ($payload, $user, $ujian, &$counts) {
            if ($user->isAdmin() && !empty($payload['DATA_KELAS'])) {
                $counts['DATA_KELAS'] = $this->importKelas($payload['DATA_KELAS']);
            }

            if (!empty($payload['DATA_SISWA'])) {
                $counts['DATA_SISWA'] = $this->importSiswa($payload['DATA_SISWA']);
            }

            if (!empty($payload['TP_LM_KKTP'])) {
                $this->importTpLmKktp($payload['TP_LM_KKTP'], $ujian);
                $counts['TP_LM_KKTP'] = count($payload['TP_LM_KKTP']);
            }

            if (!empty($payload['KUNCI_JAWABAN'])) {
                $counts['KUNCI_JAWABAN'] = $this->importKunciJawaban($payload['KUNCI_JAWABAN'], $ujian);
            }

            if (!empty($payload['DAFTAR_HADIR'])) {
                $counts['DAFTAR_HADIR'] = $this->importDaftarHadir($payload['DAFTAR_HADIR'], $ujian);
            }

            if (!empty($payload['INPUT_JAWABAN_ABCD'])) {
                $counts['INPUT_JAWABAN_ABCD'] = $this->importService->importValidated(
                    $this->resolveAnswerRowsForImport($payload['INPUT_JAWABAN_ABCD'], $ujian),
                    $ujian,
                    'abcd'
                );
            }

            if (!empty($payload['INPUT_SKOR_01'])) {
                $counts['INPUT_SKOR_01'] = $this->importService->importValidated(
                    $this->resolveAnswerRowsForImport($payload['INPUT_SKOR_01'], $ujian),
                    $ujian,
                    'biner'
                );
            }
        });

        return array_filter($counts, fn ($count) => $count > 0);
    }

    private function previewSheet(string $sheetName, array $rows, User $user, Ujian $ujian, array &$result): array
    {
        return match ($sheetName) {
            'DATA_KELAS' => $this->previewKelas($rows, $result),
            'DATA_SISWA' => $this->previewSiswa($rows, $user, $ujian, $result),
            'TP_LM_KKTP' => $this->previewTpLmKktp($rows, $result),
            'DAFTAR_HADIR' => $this->previewDaftarHadir($rows, $user, $ujian, $result),
            'KUNCI_JAWABAN' => $this->previewKunciJawaban($rows, $ujian, $result),
            'INPUT_JAWABAN_ABCD' => $this->previewJawabanRows($rows, $user, $ujian, 'abcd', 'INPUT_JAWABAN_ABCD', $result),
            'INPUT_SKOR_01' => $this->previewJawabanRows($rows, $user, $ujian, 'biner', 'INPUT_SKOR_01', $result),
            'DATA_IMPORT_SYSTEM', 'DATA_INPUT' => $this->previewTechnicalRows($rows, $user, $ujian, $sheetName, $result),
            default => [],
        };
    }

    private function previewKelas(array $rows, array &$result): array
    {
        [$header, $dataRows] = $this->extractTable($rows, ['nama_kelas', 'tingkat', 'tahun_ajaran']);
        if (!$header) {
            $result['errors'][] = 'Sheet DATA_KELAS tidak memiliki header kode_kelas, nama_kelas, tingkat, tahun_ajaran, wali_kelas.';
            return [];
        }

        $payload = [];
        foreach ($dataRows as $line => $row) {
            $assoc = $this->assoc($header, $row);
            $namaKelas = trim((string) ($assoc['nama_kelas'] ?? ''));
            $tahunAjaranName = trim((string) ($assoc['tahun_ajaran'] ?? ''));
            $tingkat = trim((string) ($assoc['tingkat'] ?? ''));

            if ($namaKelas === '' && $tahunAjaranName === '') {
                continue;
            }

            $tahunAjaran = TahunAjaran::where('tahun_ajaran', $tahunAjaranName)->first();
            if (!$tahunAjaran) {
                $result['errors'][] = "DATA_KELAS baris {$line}: tahun ajaran {$tahunAjaranName} tidak ditemukan.";
                continue;
            }

            $payload[] = [
                'nama_kelas' => $namaKelas,
                'tingkat' => $tingkat ?: $this->guessTingkat($namaKelas),
                'tahun_ajaran_id' => $tahunAjaran->id,
                'wali_kelas' => trim((string) ($assoc['wali_kelas'] ?? '')),
            ];
        }

        return $payload;
    }

    private function previewSiswa(array $rows, User $user, Ujian $ujian, array &$result): array
    {
        [$header, $dataRows] = $this->extractTable($rows, ['nis', 'nama_siswa', 'jenis_kelamin', 'kelas']);
        if (!$header) {
            $result['errors'][] = 'Sheet DATA_SISWA tidak memiliki header no, nis, nisn, nama_siswa, jenis_kelamin, kelas, status_siswa.';
            return [];
        }

        $allowedClassIds = $this->allowedStudentClassIds($user, $ujian);
        $payload = [];
        $ignored = 0;

        foreach ($dataRows as $line => $row) {
            $assoc = $this->assoc($header, $row);
            $nis = trim((string) ($assoc['nis'] ?? ''));
            $nama = trim((string) ($assoc['nama_siswa'] ?? ''));
            $kelasName = trim((string) ($assoc['kelas'] ?? ''));
            $jenisKelamin = strtoupper(trim((string) ($assoc['jenis_kelamin'] ?? '')));
            $status = strtolower(trim((string) ($assoc['status_siswa'] ?? $assoc['status'] ?? 'aktif')));

            if ($nis === '' && $nama === '') {
                continue;
            }

            $kelas = Kelas::where('nama_kelas', $kelasName)
                ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
                ->first();

            if (!$kelas) {
                $result['errors'][] = "DATA_SISWA baris {$line}: kelas {$kelasName} tidak ditemukan pada tahun ajaran ujian.";
                continue;
            }

            if (!$user->isAdmin() && !in_array($kelas->id, $allowedClassIds, true)) {
                $ignored++;
                continue;
            }

            if ($nis === '' || $nama === '') {
                $result['errors'][] = "DATA_SISWA baris {$line}: NIS dan nama_siswa wajib diisi.";
                continue;
            }

            if (!in_array($jenisKelamin, ['L', 'P'], true)) {
                $result['errors'][] = "DATA_SISWA baris {$line}: jenis_kelamin harus L atau P.";
                continue;
            }

            if (!in_array($status, ['aktif', 'nonaktif', 'lulus'], true)) {
                $result['errors'][] = "DATA_SISWA baris {$line}: status_siswa harus aktif, nonaktif, atau lulus.";
                continue;
            }

            $payload[] = [
                'nis' => $nis,
                'nisn' => trim((string) ($assoc['nisn'] ?? '')) ?: null,
                'nama_siswa' => $nama,
                'jenis_kelamin' => $jenisKelamin,
                'status' => $status,
                'kelas_id' => $kelas->id,
                'tahun_ajaran_id' => $ujian->tahun_ajaran_id,
            ];
        }

        if (!$user->isAdmin() && $ignored > 0) {
            $classNames = Kelas::whereIn('id', $allowedClassIds)->pluck('nama_kelas')->join(', ');
            $result['warnings'][] = "Sheet DATA_SISWA ditemukan, tetapi hanya siswa kelas {$classNames} yang akan diproses karena Anda login sebagai Guru wali kelas {$classNames}.";
        }

        return $payload;
    }

    private function previewTpLmKktp(array $rows, array &$result): array
    {
        [$header, $dataRows] = $this->extractTable($rows, ['kode_tp', 'tujuan_pembelajaran', 'lingkup_materi', 'kktp']);
        if (!$header) {
            return [];
        }

        $payload = [];
        foreach ($dataRows as $line => $row) {
            $assoc = $this->assoc($header, $row);
            if (trim((string) ($assoc['kode_tp'] ?? '')) === '') {
                continue;
            }

            $kktp = (int) ($assoc['kktp'] ?? 0);
            if ($kktp < 0 || $kktp > 100) {
                $result['errors'][] = "TP_LM_KKTP baris {$line}: KKTP harus angka 0-100.";
                continue;
            }

            $payload[] = $assoc;
        }

        return $payload;
    }

    private function previewDaftarHadir(array $rows, User $user, Ujian $ujian, array &$result): array
    {
        [$header, $dataRows] = $this->extractTable($rows, ['nis', 'status_kehadiran']);
        if (!$header) {
            return [];
        }

        return $this->filterAttendanceRows($header, $dataRows, $user, $ujian, 'DAFTAR_HADIR', $result);
    }

    private function previewKunciJawaban(array $rows, Ujian $ujian, array &$result): array
    {
        [$header, $dataRows] = $this->extractTable($rows, ['nomor_soal', 'kunci_jawaban', 'bobot']);
        if (!$header) {
            $result['errors'][] = 'Sheet KUNCI_JAWABAN tidak memiliki header nomor_soal, kode_tp, lingkup_materi, kunci_jawaban, bobot.';
            return [];
        }

        $payload = [];
        foreach ($dataRows as $line => $row) {
            $assoc = $this->assoc($header, $row);
            $nomor = (int) ($assoc['nomor_soal'] ?? 0);
            $kunci = strtoupper(trim((string) ($assoc['kunci_jawaban'] ?? '')));
            $bobot = (float) ($assoc['bobot'] ?? 1);

            if ($nomor <= 0) {
                continue;
            }

            if ($nomor > $ujian->jumlah_soal) {
                $result['errors'][] = "KUNCI_JAWABAN baris {$line}: nomor_soal melebihi jumlah soal ujian.";
                continue;
            }

            if (!in_array($kunci, ['A', 'B', 'C', 'D', 'E'], true)) {
                $result['errors'][] = "KUNCI_JAWABAN baris {$line}: kunci_jawaban harus A/B/C/D/E.";
                continue;
            }

            $payload[] = [
                'nomor_soal' => $nomor,
                'kunci_jawaban' => $kunci,
                'bobot' => $bobot > 0 ? $bobot : 1,
            ];
        }

        return $payload;
    }

    private function previewJawabanRows(array $rows, User $user, Ujian $ujian, string $mode, string $sheetName, array &$result): array
    {
        $prefix = $mode === 'biner' ? 'skor_' : 'soal_';
        [$header, $dataRows] = $this->extractTable($rows, ['nis', 'nama_siswa', 'status_kehadiran', $prefix . '1']);
        if (!$header) {
            return [];
        }

        $expectedRows = $this->buildExpectedAnswerRows($header, $dataRows, $user, $ujian, $mode, $sheetName, $result);
        if (empty($expectedRows)) {
            return [];
        }

        return $this->previewCompleteAnswerRows($expectedRows, $ujian, $mode, $sheetName, $result);
    }

    private function previewTechnicalRows(array $rows, User $user, Ujian $ujian, string $sheetName, array &$result): array
    {
        $header = $rows[0] ?? [];
        if ($this->importService->isHeaderValid($header, $ujian, 'biner')) {
            $preview = $this->importService->preview(array_slice($rows, 1), $ujian, 'biner');
            $this->mergeImportErrors($preview, $sheetName, $result);
            return $preview['valid'];
        }

        if ($this->importService->isHeaderValid($header, $ujian, 'abcd')) {
            $preview = $this->importService->preview(array_slice($rows, 1), $ujian, 'abcd');
            $this->mergeImportErrors($preview, $sheetName, $result);
            return $preview['valid'];
        }

        return [];
    }

    private function payloadTargetSheet(string $sheetName, array $rows, Ujian $ujian): string
    {
        if (!in_array($sheetName, ['DATA_IMPORT_SYSTEM', 'DATA_INPUT'], true)) {
            return $sheetName;
        }

        $header = $rows[0] ?? [];
        if ($this->importService->isHeaderValid($header, $ujian, 'biner')) {
            return 'INPUT_SKOR_01';
        }

        if ($this->importService->isHeaderValid($header, $ujian, 'abcd')) {
            return 'INPUT_JAWABAN_ABCD';
        }

        return $sheetName;
    }

    private function buildExpectedAnswerRows(array $header, array $dataRows, User $user, Ujian $ujian, string $mode, string $sheetName, array &$result): array
    {
        $expectedRows = [];
        $ignored = 0;
        $prefix = $mode === 'biner' ? 'skor_' : 'soal_';

        foreach ($dataRows as $line => $row) {
            $assoc = $this->assoc($header, $row);
            $nis = trim((string) ($assoc['nis'] ?? ''));
            if ($nis === '') {
                continue;
            }

            if (!$this->isNisAllowedForUjian($nis, $ujian)) {
                $ignored++;
                continue;
            }

            $status = $this->normalizeAttendance((string) ($assoc['status_kehadiran'] ?? 'hadir'));
            $converted = [
                $nis,
                trim((string) ($assoc['nisn'] ?? '')),
                trim((string) ($assoc['nama_siswa'] ?? '')),
                strtoupper(trim((string) ($assoc['jenis_kelamin'] ?? ''))),
                $status,
            ];

            for ($i = 1; $i <= $ujian->jumlah_soal; $i++) {
                $converted[] = $assoc[$prefix . $i] ?? '';
            }

            $expectedRows[] = $converted;
        }

        if ($ignored > 0) {
            $result['warnings'][] = "{$sheetName}: {$ignored} baris diabaikan karena siswa tidak termasuk kelas ujian atau tidak sesuai hak akses.";
        }

        return $expectedRows;
    }

    private function filterAttendanceRows(array $header, array $dataRows, User $user, Ujian $ujian, string $sheetName, array &$result): array
    {
        $payload = [];
        $ignored = 0;
        foreach ($dataRows as $row) {
            $assoc = $this->assoc($header, $row);
            $nis = trim((string) ($assoc['nis'] ?? ''));
            if ($nis === '') {
                continue;
            }

            if (!$this->isNisAllowedForUjian($nis, $ujian)) {
                $ignored++;
                continue;
            }

            $payload[] = [
                'nis' => $nis,
                'status_kehadiran' => $this->normalizeAttendance((string) ($assoc['status_kehadiran'] ?? 'hadir')),
            ];
        }

        if ($ignored > 0) {
            $result['warnings'][] = "{$sheetName}: {$ignored} baris diabaikan karena siswa tidak termasuk kelas ujian atau tidak sesuai hak akses.";
        }

        return $payload;
    }

    private function importKelas(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $wali = $row['wali_kelas']
                ? Guru::where('nama_guru', $row['wali_kelas'])->first()
                : null;

            Kelas::updateOrCreate(
                [
                    'nama_kelas' => $row['nama_kelas'],
                    'tahun_ajaran_id' => $row['tahun_ajaran_id'],
                ],
                [
                    'tingkat' => $row['tingkat'],
                    'wali_kelas_id' => $wali?->id,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function importSiswa(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $siswa = Siswa::updateOrCreate(
                ['nis' => $row['nis']],
                [
                    'nisn' => $row['nisn'],
                    'nama_siswa' => $row['nama_siswa'],
                    'jenis_kelamin' => $row['jenis_kelamin'],
                    'status' => $row['status'],
                ]
            );

            SiswaKelas::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $row['kelas_id'],
                    'tahun_ajaran_id' => $row['tahun_ajaran_id'],
                ],
                ['status' => match ($row['status']) {
                    'aktif' => 'aktif',
                    'lulus' => 'lulus',
                    default => 'pindah',
                }]
            );

            $count++;
        }

        return $count;
    }

    private function importTpLmKktp(array $rows, Ujian $ujian): void
    {
        $first = $rows[0] ?? null;
        if (!$first) {
            return;
        }

        $ujian->update([
            'tujuan_pembelajaran' => $first['tujuan_pembelajaran'] ?? $ujian->tujuan_pembelajaran,
            'lingkup_materi' => $first['lingkup_materi'] ?? $ujian->lingkup_materi,
            'kktp' => $first['kktp'] ?? $ujian->kktp,
            'kkm' => $first['kktp'] ?? $ujian->kkm,
        ]);
    }

    private function importKunciJawaban(array $rows, Ujian $ujian): int
    {
        $count = 0;
        foreach ($rows as $row) {
            Soal::updateOrCreate(
                ['ujian_id' => $ujian->id, 'nomor_soal' => $row['nomor_soal']],
                ['kunci_jawaban' => $row['kunci_jawaban'], 'bobot' => $row['bobot']]
            );
            $count++;
        }

        if ($count >= $ujian->jumlah_soal) {
            $ujian->update(['status' => 'kunci_lengkap']);
        }

        return $count;
    }

    private function importDaftarHadir(array $rows, Ujian $ujian): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $siswa = Siswa::where('nis', $row['nis'])->first();
            if (!$siswa) {
                continue;
            }

            $kelasId = SiswaKelas::where('siswa_id', $siswa->id)
                ->whereIn('kelas_id', $ujian->kelas()->pluck('kelas.id'))
                ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
                ->value('kelas_id');

            if (!$kelasId) {
                continue;
            }

            PesertaUjian::updateOrCreate(
                ['ujian_id' => $ujian->id, 'siswa_id' => $siswa->id],
                [
                    'kelas_id' => $kelasId,
                    'status_kehadiran' => $row['status_kehadiran'],
                    'jumlah_benar' => 0,
                    'jumlah_salah' => 0,
                    'total_skor' => 0,
                    'nilai' => 0,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function mergeImportErrors(array $preview, string $sheetName, array &$result): void
    {
        foreach ($preview['errors'] as $error) {
            foreach ($error['errors'] as $message) {
                $result['errors'][] = "{$sheetName}: {$message}";
            }
        }
    }

    private function previewCompleteAnswerRows(array $rows, Ujian $ujian, string $mode, string $sheetName, array &$result): array
    {
        $ujian->loadMissing(['soal', 'kelas']);
        $soalList = $ujian->soal->sortBy('nomor_soal')->values();
        $kelasUjianIds = $ujian->kelas->pluck('id')->all();
        $pendingStudents = $this->pendingStudentsByNis($result);
        $validRows = [];
        $nisTracker = [];

        foreach ($rows as $rowIndex => $row) {
            $lineNum = $rowIndex + 2;
            $nis = trim((string) ($row[0] ?? ''));
            $nisn = trim((string) ($row[1] ?? ''));
            $nama = trim((string) ($row[2] ?? ''));
            $jk = strtoupper(trim((string) ($row[3] ?? '')));
            $statusHadir = $this->normalizeAttendance((string) ($row[4] ?? 'hadir'));
            $jawabanCols = array_slice($row, 5);
            $rowErrors = [];

            if ($nis === '') {
                $rowErrors[] = "Baris {$lineNum}: NIS wajib diisi.";
            }

            if ($nis !== '' && isset($nisTracker[$nis])) {
                $rowErrors[] = "Baris {$lineNum}: NIS {$nis} duplikat dengan baris {$nisTracker[$nis]}.";
            } elseif ($nis !== '') {
                $nisTracker[$nis] = $lineNum;
            }

            $siswa = $nis !== '' ? Siswa::where('nis', $nis)->first() : null;
            $siswaKelas = null;
            $pending = $pendingStudents[$nis] ?? null;

            if ($siswa) {
                $siswaKelas = SiswaKelas::where('siswa_id', $siswa->id)
                    ->whereIn('kelas_id', $kelasUjianIds)
                    ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
                    ->where('status', 'aktif')
                    ->orderBy('id')
                    ->first();
            }

            if (!$siswa && !$pending && $nis !== '') {
                $rowErrors[] = "Baris {$lineNum}: NIS {$nis} tidak ditemukan di database dan tidak ada di sheet DATA_SISWA.";
            }

            if ($siswa && !$siswaKelas && !$pending) {
                $rowErrors[] = "Baris {$lineNum}: NIS {$nis} tidak terdaftar pada kelas ujian.";
            }

            if ($pending && !in_array((int) $pending['kelas_id'], $kelasUjianIds, true)) {
                $rowErrors[] = "Baris {$lineNum}: NIS {$nis} pada DATA_SISWA tidak termasuk kelas ujian.";
            }

            if (!in_array($statusHadir, ['hadir', 'tidak_hadir'], true)) {
                $rowErrors[] = "Baris {$lineNum}: status kehadiran harus hadir atau tidak_hadir.";
            }

            if (count($jawabanCols) !== $soalList->count()) {
                $rowErrors[] = "Baris {$lineNum}: jumlah kolom jawaban harus {$soalList->count()}, ditemukan " . count($jawabanCols) . '.';
            }

            foreach ($soalList as $idx => $soal) {
                $value = strtoupper(preg_replace('/\s+/', '', (string) ($jawabanCols[$idx] ?? '')));
                if ($value === '') {
                    continue;
                }

                if ($mode === 'abcd' && !in_array($value, ['A', 'B', 'C', 'D', 'E'], true)) {
                    $rowErrors[] = "Baris {$lineNum}, soal {$soal->nomor_soal}: jawaban harus A/B/C/D/E atau kosong.";
                }

                if ($mode === 'biner' && !in_array($value, ['0', '1'], true)) {
                    $rowErrors[] = "Baris {$lineNum}, soal {$soal->nomor_soal}: skor harus 0 atau 1.";
                }
            }

            foreach ($rowErrors as $message) {
                $result['errors'][] = "{$sheetName}: {$message}";
            }

            if ($rowErrors) {
                continue;
            }

            $validRows[] = [
                'row_index' => $rowIndex,
                'nis' => $nis,
                'nisn' => $nisn ?: ($pending['nisn'] ?? null),
                'nama' => $siswa->nama_siswa ?? $pending['nama_siswa'] ?? $nama,
                'jenis_kelamin' => in_array($jk, ['L', 'P'], true)
                    ? $jk
                    : ($siswa->jenis_kelamin ?? $pending['jenis_kelamin'] ?? 'L'),
                'status_kehadiran' => $statusHadir,
                'siswa_id' => $siswa?->id,
                'kelas_id' => $siswaKelas?->kelas_id ?? $pending['kelas_id'] ?? null,
                'jawaban' => array_map(
                    fn ($value) => strtoupper(preg_replace('/\s+/', '', (string) $value)),
                    array_slice($jawabanCols, 0, $soalList->count())
                ),
            ];
        }

        return $validRows;
    }

    private function pendingStudentsByNis(array $result): array
    {
        $students = [];
        foreach (($result['payload']['DATA_SISWA'] ?? []) as $row) {
            if (!empty($row['nis'])) {
                $students[$row['nis']] = $row;
            }
        }

        return $students;
    }

    private function resolveAnswerRowsForImport(array $rows, Ujian $ujian): array
    {
        $resolved = [];
        $kelasUjianIds = $ujian->kelas()->pluck('kelas.id')->all();

        foreach ($rows as $row) {
            if (!empty($row['siswa_id']) && !empty($row['kelas_id'])) {
                $resolved[] = $row;
                continue;
            }

            $siswa = Siswa::where('nis', $row['nis'] ?? '')->first();
            if (!$siswa) {
                continue;
            }

            $kelasId = SiswaKelas::where('siswa_id', $siswa->id)
                ->whereIn('kelas_id', $kelasUjianIds)
                ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
                ->where('status', 'aktif')
                ->value('kelas_id');

            if (!$kelasId) {
                continue;
            }

            $row['siswa_id'] = $siswa->id;
            $row['kelas_id'] = $kelasId;
            $resolved[] = $row;
        }

        return $resolved;
    }

    private function canProcessSheet(string $sheetName, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->isGuru()) {
            return false;
        }

        return $sheetName !== 'DATA_KELAS';
    }

    private function ignoreReasonForSheet(string $sheetName, User $user, Ujian $ujian): string
    {
        if ($user->isGuru() && $sheetName === 'DATA_KELAS') {
            return 'DATA_KELAS diabaikan karena Guru tidak boleh memproses data kelas global.';
        }

        return 'Sheet diabaikan karena role pengguna tidak memiliki hak memproses data tersebut.';
    }

    private function allowedStudentClassIds(User $user, Ujian $ujian): array
    {
        if ($user->isAdmin()) {
            return $ujian->kelas()->pluck('kelas.id')->all();
        }

        return $user->guru?->kelasWali()->pluck('kelas.id')->all() ?? [];
    }

    private function isNisAllowedForUjian(string $nis, Ujian $ujian): bool
    {
        $siswa = Siswa::where('nis', $nis)->first();
        if (!$siswa) {
            return true;
        }

        return SiswaKelas::where('siswa_id', $siswa->id)
            ->whereIn('kelas_id', $ujian->kelas()->pluck('kelas.id'))
            ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
            ->where('status', 'aktif')
            ->exists();
    }

    private function normalizeAttendance(string $status): string
    {
        $status = str_replace([' ', '-'], '_', strtolower(trim($status)));
        return $status === 'hadir' ? 'hadir' : 'tidak_hadir';
    }

    private function extractTable(array $rows, array $requiredHeaders): array
    {
        foreach ($rows as $idx => $row) {
            $header = array_map(fn ($value) => $this->normalizeHeaderValue($value), $row);
            $header = array_values(array_filter($header, fn ($value) => $value !== ''));
            if (empty($header)) {
                continue;
            }

            $containsAll = collect($requiredHeaders)->every(fn ($required) => in_array($required, $header, true));
            if ($containsAll) {
                $dataRows = array_slice($rows, $idx + 1);
                $mapped = [];
                foreach ($dataRows as $offset => $dataRow) {
                    $mapped[$idx + 2 + $offset] = $dataRow;
                }

                return [$header, $mapped];
            }
        }

        return [null, []];
    }

    private function assoc(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $idx => $key) {
            $assoc[$key] = $row[$idx] ?? null;
        }

        return $assoc;
    }

    private function normalizeHeaderValue(mixed $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');

        return match ($value) {
            'no_soal' => 'nomor_soal',
            'no_soal_' => 'nomor_soal',
            'nama_murid', 'nama' => 'nama_siswa',
            'l_p', 'lp', 'jk' => 'jenis_kelamin',
            'status' => 'status_siswa',
            'status_hadir' => 'status_kehadiran',
            'kunci' => 'kunci_jawaban',
            default => $value,
        };
    }

    private function filledRows(array $rows): array
    {
        return array_values(array_filter($rows, function ($row) {
            return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) > 0;
        }));
    }

    private function guessTingkat(string $namaKelas): string
    {
        $parts = preg_split('/\s+/', trim($namaKelas));
        return $parts[0] ?? '';
    }
}

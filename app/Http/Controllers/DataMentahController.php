<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\SiswaKelas;
use App\Models\LogAktivitas;
use App\Exports\AssessmentTemplateExport;
use App\Services\ScoringService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class DataMentahController extends Controller
{
    protected $scoringService;
    protected $importService;

    public function __construct(ScoringService $scoringService, ImportService $importService)
    {
        $this->scoringService = $scoringService;
        $this->importService  = $importService;
    }

    /**
     * Tampilkan halaman Data Mentah T1
     */
    public function index(Ujian $ujian)
    {
        $this->syncPesertaUjian($ujian);

        $ujian->load([
            'guru:id,nama_guru',
            'mapel:id,nama_mapel',
            'tahunAjaran:id,tahun_ajaran,semester',
            'kelas:id,nama_kelas',
            'soal' => fn ($query) => $query
                ->select(['id', 'ujian_id', 'nomor_soal', 'kunci_jawaban'])
                ->orderBy('nomor_soal'),
        ]);

        // Ambil hanya satu halaman peserta. Jawaban dibatasi pada peserta di halaman ini.
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->with([
                'siswa:id,nis,nisn,nama_siswa,jenis_kelamin',
                'jawabanSiswa' => fn ($query) => $query
                    ->select(['id', 'peserta_ujian_id', 'soal_id', 'jawaban', 'skor_biner', 'is_benar']),
            ])
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->paginate(10)
            ->withQueryString();

        return view('data_mentah.index', compact('ujian', 'peserta'));
    }

    /**
     * Simpan input manual jawaban A/B/C/D/E atau skor 0/1.
     */
    public function storeManual(Request $request, Ujian $ujian)
    {
        $request->validate([
            'mode' => 'required|in:abcd,biner',
            'status_kehadiran' => 'required|array',
        ]);

        if (!$ujian->isKunciLengkap()) {
            return back()->with('error', 'Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.');
        }

        $mode = $request->input('mode');
        $ujian->load('soal');
        $soalList = $ujian->soal->sortBy('nomor_soal')->values();

        try {
            DB::transaction(function () use ($request, $ujian, $mode, $soalList) {
                foreach ($request->input('status_kehadiran', []) as $pesertaId => $statusKehadiran) {
                    if (!in_array($statusKehadiran, ['hadir', 'tidak_hadir'])) {
                        throw new \Exception('Status kehadiran hanya boleh hadir atau tidak_hadir.');
                    }

                    $peserta = PesertaUjian::where('ujian_id', $ujian->id)
                        ->where('id', $pesertaId)
                        ->firstOrFail();

                    $peserta->update(['status_kehadiran' => $statusKehadiran]);

                    foreach ($soalList as $soal) {
                        if ($statusKehadiran === 'tidak_hadir') {
                            JawabanSiswa::updateOrCreate(
                                ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soal->id],
                                ['jawaban' => null, 'skor_biner' => 0, 'is_benar' => false]
                            );
                            continue;
                        }

                        if ($mode === 'abcd') {
                            $answers = $request->input('jawaban', []);
                            $raw = strtoupper(preg_replace('/\s+/', '', (string) ($answers[$peserta->id][$soal->id] ?? '')));

                            if ($raw !== '' && !in_array($raw, ['A', 'B', 'C', 'D', 'E'])) {
                                throw new \Exception("Jawaban peserta {$peserta->id} soal {$soal->nomor_soal} harus A/B/C/D/E atau kosong.");
                            }

                            JawabanSiswa::updateOrCreate(
                                ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soal->id],
                                ['jawaban' => $raw ?: null, 'skor_biner' => 0, 'is_benar' => false]
                            );
                        } else {
                            $scores = $request->input('skor', []);
                            $raw = trim((string) ($scores[$peserta->id][$soal->id] ?? '0'));
                            $raw = $raw === '' ? '0' : $raw;

                            if (!in_array($raw, ['0', '1'], true)) {
                                throw new \Exception("Skor peserta {$peserta->id} soal {$soal->nomor_soal} harus 0 atau 1.");
                            }

                            $skorBiner = (int) $raw;
                            JawabanSiswa::updateOrCreate(
                                ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soal->id],
                                ['jawaban' => null, 'skor_biner' => $skorBiner, 'is_benar' => $skorBiner === 1]
                            );
                        }
                    }
                }
            });

            LogAktivitas::catat("Input manual Data Mentah T1 ({$mode}) ujian: {$ujian->nama_ujian}", 'Data Mentah');

            return redirect()->route('data-mentah.index', $ujian)
                ->with('success', 'Input manual berhasil disimpan. Klik Proses Data Mentah T1 untuk menghitung nilai.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Proses scoring data mentah (konversi A/B/C/D/E → 0/1)
     */
    public function proses(Request $request, Ujian $ujian)
    {
        return redirect()->route('data-mentah.index', $ujian)
            ->with('warning', 'Proses T1 sekarang berjalan bertahap. Klik tombol Proses Data Mentah T1 pada halaman ini.');
    }

    public function prosesStart(Request $request, Ujian $ujian)
    {
        $validated = $request->validate([
            'mode' => 'required|in:abcd,biner',
            'reset' => 'sometimes|boolean',
        ]);

        $sessionKey = $this->batchSessionKey($ujian);
        $existing = $request->session()->get($sessionKey);

        if (!$request->boolean('reset') && is_array($existing) && ($existing['status'] ?? null) === 'running') {
            return response()->json($existing);
        }

        try {
            $total = $this->scoringService->prepareBatch($ujian->id);
            $state = [
                'status' => $total === 0 ? 'completed' : 'running',
                'total' => $total,
                'processed' => 0,
                'remaining' => $total,
                'progress' => $total === 0 ? 100 : 0,
                'next_offset' => 0,
                'mode' => $validated['mode'],
                'message' => $total === 0
                    ? 'Tidak ada peserta yang perlu diproses.'
                    : "Proses T1 dimulai untuk {$total} peserta.",
            ];

            $request->session()->put($sessionKey, $state);

            return response()->json($state);
        } catch (Throwable $exception) {
            return $this->batchFailureResponse($request, $ujian, $validated['mode'], $exception);
        }
    }

    public function prosesBatch(Request $request, Ujian $ujian)
    {
        $validated = $request->validate([
            'offset' => 'required|integer|min:0',
            'limit' => 'required|integer|min:1|max:' . ScoringService::MAX_BATCH_SIZE,
            'mode' => 'required|in:abcd,biner',
        ]);

        $sessionKey = $this->batchSessionKey($ujian);
        $state = $request->session()->get($sessionKey);

        if (!is_array($state)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Proses T1 belum dimulai. Silakan mulai ulang proses.',
            ], 409);
        }

        if (($state['status'] ?? null) === 'completed') {
            return response()->json($state);
        }

        if (($state['mode'] ?? null) !== $validated['mode'] || (int) ($state['next_offset'] ?? -1) !== (int) $validated['offset']) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Urutan batch tidak sesuai. Silakan mulai ulang proses T1.',
            ], 409);
        }

        try {
            $result = $this->scoringService->processBatch(
                $ujian->id,
                $validated['mode'],
                (int) $validated['offset'],
                (int) $validated['limit']
            );

            $result['mode'] = $validated['mode'];
            $result['message'] = $result['status'] === 'completed'
                ? 'Proses T1 selesai.'
                : "Memproses {$result['processed']} dari {$result['total']} peserta.";

            $request->session()->put($sessionKey, $result);

            if ($result['status'] === 'completed') {
                $request->session()->flash('success', 'Data Mentah T1 berhasil diproses secara bertahap.');
                LogAktivitas::catat("Memproses Data Mentah T1 ujian: {$ujian->nama_ujian}", 'Data Mentah');
            }

            return response()->json($result);
        } catch (Throwable $exception) {
            return $this->batchFailureResponse($request, $ujian, $validated['mode'], $exception);
        }
    }

    public function prosesStatus(Request $request, Ujian $ujian)
    {
        $state = $request->session()->get($this->batchSessionKey($ujian));

        if (is_array($state)) {
            return response()->json($state);
        }

        $total = PesertaUjian::where('ujian_id', $ujian->id)->count();

        return response()->json([
            'status' => 'idle',
            'total' => $total,
            'processed' => 0,
            'remaining' => $total,
            'progress' => 0,
            'next_offset' => 0,
            'message' => 'Proses T1 belum dimulai.',
        ]);
    }

    /**
     * Download template import (CSV) — dengan kolom nisn, jenis_kelamin sesuai spec F2/F3
     */
    public function downloadTemplate(Ujian $ujian, $type = 'abcd', ?string $filename = null)
    {
        $mode = in_array($type, ['biner', 'skor'], true) ? 'biner' : 'abcd';
        $filename = 'template_data_mentah_' . ($mode === 'biner' ? 'skor_01' : 'jawaban_abcd') . '_' .
                    str_replace(' ', '_', $ujian->nama_ujian) . '.xlsx';

        LogAktivitas::catat('Download template Data Mentah T1', 'Data Mentah', "Download template Data Mentah T1 ({$mode}) ujian: {$ujian->nama_ujian}");

        return Excel::download(new AssessmentTemplateExport($mode === 'biner' ? 'skor-01' : 'jawaban-abcd', $ujian), $filename);
    }

    /**
     * Preview import — validasi file, tampilkan preview + error detail (F4-F7)
     */
    public function preview(Request $request, Ujian $ujian)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'mode' => 'required|in:abcd,biner',
        ]);

        try {
            $mode = $request->input('mode', 'abcd');
            $rows = $this->parseFile($request->file('file'), $ujian, $mode);

            // Ambil header, skip header row
            $header = array_shift($rows);
            if (!$header || !$this->importService->isHeaderValid($header, $ujian, $mode)) {
                $expected = implode(', ', $this->importService->expectedHeader($ujian, $mode));
                $received = $header ? implode(', ', $this->importService->normalizeHeader($header)) : '-';

                return back()->with(
                    'error',
                    "Format header tidak sesuai. Header yang benar: {$expected}. Header file: {$received}."
                );
            }

            $result = $this->importService->preview($rows, $ujian, $mode);

            // Simpan hanya row valid ke session untuk confirm import.
            session([
                'import_preview_data' => $result['valid'],
                'import_preview_mode' => $mode,
                'import_preview_ujian_id' => $ujian->id,
                'import_preview_error_count' => $result['summary']['error_count'],
            ]);

            return view('data_mentah.preview', [
                'ujian' => $ujian,
                'mode' => $mode,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal preview: ' . $e->getMessage());
        }
    }

    /**
     * Confirm import — simpan data yang sudah divalidasi (F8)
     */
    public function confirmImport(Request $request, Ujian $ujian)
    {
        $rows = session('import_preview_data');
        $mode = session('import_preview_mode');

        if (!$rows || session('import_preview_ujian_id') !== $ujian->id) {
            return back()->with('error', 'Session import sudah kadaluarsa. Silakan upload ulang.');
        }

        if ((int) session('import_preview_error_count', 0) > 0) {
            return redirect()->route('data-mentah.index', $ujian)
                ->with('error', 'Import belum bisa dikonfirmasi karena masih ada data error. Perbaiki file lalu upload ulang.');
        }

        try {
            $imported = $this->importService->importValidated($rows, $ujian, $mode);

            // Clear session
            session()->forget(['import_preview_data', 'import_preview_mode', 'import_preview_ujian_id', 'import_preview_error_count']);

            LogAktivitas::catat(
                "Import template Data Mentah T1 ({$mode}) ujian: {$ujian->nama_ujian}",
                'Data Mentah',
                "Import template Excel Data Mentah T1 ({$mode}) untuk ujian: {$ujian->nama_ujian} - {$imported} siswa",
                Ujian::class,
                $ujian->id
            );

            return redirect()->route('data-mentah.index', $ujian)
                             ->with('success', "Import berhasil. {$imported} data siswa tersimpan. Silakan proses scoring.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Import langsung (backward compatible — tanpa preview)
     */
    public function import(Request $request, Ujian $ujian)
    {
        // Redirect ke preview flow
        return $this->preview($request, $ujian);
    }

    /**
     * Parse file CSV/Excel ke array rows
     */
    private function parseFile($file, Ujian $ujian, string $mode): array
    {
        $extension = $file->getClientOriginalExtension();

        if (in_array($extension, ['csv', 'txt'])) {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
            return $this->filledRows($rows);
        }

        // Workbook hanya dibaca sekali. Prioritaskan dua sheet sistem dan hentikan
        // pencarian segera setelah format valid ditemukan.
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $candidateNames = ['DATA_IMPORT_SYSTEM', 'DATA_INPUT'];

        foreach ($candidateNames as $candidateName) {
            $sheet = $spreadsheet->getSheetByName($candidateName);
            if ($sheet === null) {
                continue;
            }

            $rows = $this->filledRows($sheet->toArray(null, true, true, false));
            $header = $rows[0] ?? null;
            if ($header && $this->importService->isHeaderValid($header, $ujian, $mode)) {
                return $rows;
            }
        }

        // Fallback untuk template lama/non-sistem.
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (in_array(strtoupper(trim($sheet->getTitle())), $candidateNames, true)) {
                continue;
            }

            $rows = $this->filledRows($sheet->toArray(null, true, true, false));
            $header = $rows[0] ?? null;
            if ($header && $this->importService->isHeaderValid($header, $ujian, $mode)) {
                return $rows;
            }

            $normalizedRows = $this->normalizeSpecificTemplateRows($rows, $ujian, $mode);
            if ($normalizedRows) {
                return $normalizedRows;
            }
        }

        throw new \Exception("Template tidak valid. Gunakan template resmi dari SIABSoal.");
    }

    private function normalizeSpecificTemplateRows(array $rows, Ujian $ujian, string $mode): ?array
    {
        $prefix = $mode === 'biner' ? 'skor_' : 'soal_';
        $headerRowIndex = null;
        $header = [];

        foreach ($rows as $idx => $row) {
            $normalized = array_map(fn ($value) => $this->normalizeImportHeaderValue($value), $row);
            if (in_array('nis', $normalized, true) && in_array('status_kehadiran', $normalized, true) && in_array($prefix . '1', $normalized, true)) {
                $headerRowIndex = $idx;
                $header = $normalized;
                break;
            }
        }

        if ($headerRowIndex === null) {
            return null;
        }

        $expectedHeader = $this->importService->expectedHeader($ujian, $mode);
        $normalizedRows = [$expectedHeader];

        foreach (array_slice($rows, $headerRowIndex + 1) as $row) {
            $assoc = [];
            foreach ($header as $idx => $key) {
                $assoc[$key] = $row[$idx] ?? null;
            }

            $nis = trim((string) ($assoc['nis'] ?? ''));
            if ($nis === '') {
                continue;
            }

            $converted = [
                $nis,
                trim((string) ($assoc['nisn'] ?? '')),
                trim((string) ($assoc['nama_siswa'] ?? '')),
                strtoupper(trim((string) ($assoc['jenis_kelamin'] ?? ''))),
                $this->normalizeAttendanceForImport((string) ($assoc['status_kehadiran'] ?? 'hadir')),
            ];

            for ($i = 1; $i <= $ujian->jumlah_soal; $i++) {
                $converted[] = $assoc[$prefix . $i] ?? '';
            }

            $normalizedRows[] = $converted;
        }

        return count($normalizedRows) > 1 ? $normalizedRows : null;
    }

    private function normalizeImportHeaderValue(mixed $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');

        return match ($value) {
            'nama_murid', 'nama' => 'nama_siswa',
            'l_p', 'lp', 'jk' => 'jenis_kelamin',
            'status_hadir' => 'status_kehadiran',
            default => $value,
        };
    }

    private function normalizeAttendanceForImport(string $status): string
    {
        $status = str_replace([' ', '-'], '_', strtolower(trim($status)));
        return $status === 'hadir' ? 'hadir' : 'tidak_hadir';
    }

    private function filledRows(array $rows): array
    {
        return array_values(array_filter($rows, function ($row) {
            return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) > 0;
        }));
    }

    /**
     * Sinkronkan peserta dari kelas ujian agar guru bisa input manual tanpa import awal.
     */
    private function syncPesertaUjian(Ujian $ujian): void
    {
        $ujian->loadMissing('kelas');
        $kelasIds = $ujian->kelas->pluck('id');

        if ($kelasIds->isEmpty()) {
            return;
        }

        $siswaKelas = SiswaKelas::query()
            ->whereIn('kelas_id', $kelasIds)
            ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
            ->where('status', 'aktif')
            ->whereHas('siswa', fn ($query) => $query->where('status', 'aktif'))
            ->orderBy('id')
            ->get(['id', 'siswa_id', 'kelas_id'])
            ->unique('siswa_id');

        if ($siswaKelas->isEmpty()) {
            return;
        }

        $existingSiswaIds = PesertaUjian::where('ujian_id', $ujian->id)
            ->whereIn('siswa_id', $siswaKelas->pluck('siswa_id'))
            ->pluck('siswa_id')
            ->all();
        $existingLookup = array_fill_keys($existingSiswaIds, true);
        $timestamp = now();

        $rows = $siswaKelas
            ->reject(fn (SiswaKelas $siswaKelas) => isset($existingLookup[$siswaKelas->siswa_id]))
            ->map(fn (SiswaKelas $siswaKelas) => [
                'ujian_id' => $ujian->id,
                'siswa_id' => $siswaKelas->siswa_id,
                'kelas_id' => $siswaKelas->kelas_id,
                'status_kehadiran' => 'hadir',
                'jumlah_benar' => 0,
                'jumlah_salah' => 0,
                'total_skor' => 0,
                'nilai' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            PesertaUjian::insertOrIgnore($rows);
        }
    }

    private function batchSessionKey(Ujian $ujian): string
    {
        return 'data_mentah_batch_' . $ujian->id;
    }

    private function batchFailureResponse(Request $request, Ujian $ujian, string $mode, Throwable $exception)
    {
        Log::error('Batch T1 gagal', [
            'ujian_id' => $ujian->id,
            'user_id' => $request->user()?->id,
            'exception' => $exception,
        ]);

        $safeMessages = [
            'Kunci jawaban belum lengkap. Lengkapi kunci jawaban terlebih dahulu.',
            'Mode proses T1 tidak valid.',
            'Offset batch tidak boleh negatif.',
            'Offset batch melebihi jumlah peserta. Mulai ulang proses T1.',
            'Peserta untuk batch berikutnya tidak ditemukan. Mulai ulang proses T1.',
        ];
        $publicMessage = in_array($exception->getMessage(), $safeMessages, true)
            ? $exception->getMessage()
            : 'Proses T1 gagal pada batch tertentu. Silakan ulangi proses atau hubungi admin.';

        $state = [
            'status' => 'failed',
            'total' => PesertaUjian::where('ujian_id', $ujian->id)->count(),
            'processed' => (int) data_get($request->session()->get($this->batchSessionKey($ujian)), 'processed', 0),
            'remaining' => (int) data_get($request->session()->get($this->batchSessionKey($ujian)), 'remaining', 0),
            'progress' => (float) data_get($request->session()->get($this->batchSessionKey($ujian)), 'progress', 0),
            'next_offset' => (int) data_get($request->session()->get($this->batchSessionKey($ujian)), 'next_offset', 0),
            'mode' => $mode,
            'message' => $publicMessage,
        ];

        $request->session()->put($this->batchSessionKey($ujian), $state);

        return response()->json($state, 500);
    }
}

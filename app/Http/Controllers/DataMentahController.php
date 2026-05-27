<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\SiswaKelas;
use App\Models\LogAktivitas;
use App\Exports\DataMentahTemplateExport;
use App\Imports\SpreadsheetRowsImport;
use App\Services\ScoringService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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

        $ujian->load(['soal', 'pesertaUjian.siswa', 'pesertaUjian.jawabanSiswa']);

        // Ambil peserta ujian sorted by siswa name
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->with(['siswa', 'jawabanSiswa'])
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->get();

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
        try {
            $mode = $request->input('mode', 'abcd'); // 'abcd' atau 'biner'

            if ($mode === 'biner') {
                $this->scoringService->prosesSkorBiner($ujian->id);
            } else {
                $this->scoringService->prosesJawabanABCD($ujian->id);
            }

            LogAktivitas::catat("Memproses Data Mentah T1 ujian: {$ujian->nama_ujian}", 'Data Mentah');

            return redirect()->route('data-mentah.index', $ujian)
                             ->with('success', 'Data Mentah T1 berhasil diproses.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download template import (CSV) — dengan kolom nisn, jenis_kelamin sesuai spec F2/F3
     */
    public function downloadTemplate(Ujian $ujian, $type = 'abcd')
    {
        $mode = in_array($type, ['biner', 'skor'], true) ? 'biner' : 'abcd';
        $filename = 'template_data_mentah_' . ($mode === 'biner' ? 'skor_01' : 'jawaban_abcd') . '_' .
                    str_replace(' ', '_', $ujian->nama_ujian) . '.xlsx';

        return Excel::download(new DataMentahTemplateExport($ujian, $mode), $filename);
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
            $rows = $this->parseFile($request->file('file'));
            $mode = $request->input('mode', 'abcd');

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

            LogAktivitas::catat("Import Data Mentah T1 ({$mode}) ujian: {$ujian->nama_ujian} — {$imported} siswa", 'Data Mentah');

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
    private function parseFile($file): array
    {
        $extension = $file->getClientOriginalExtension();

        if (in_array($extension, ['csv', 'txt'])) {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
        } else {
            $data = Excel::toArray(new SpreadsheetRowsImport(), $file);
            $rows = $data[0] ?? [];
        }

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

        $siswaKelas = SiswaKelas::with('siswa')
            ->whereIn('kelas_id', $kelasIds)
            ->where('tahun_ajaran_id', $ujian->tahun_ajaran_id)
            ->where('status', 'aktif')
            ->whereHas('siswa', fn ($query) => $query->where('status', 'aktif'))
            ->orderBy('id')
            ->get()
            ->unique('siswa_id');

        foreach ($siswaKelas as $sk) {
            PesertaUjian::firstOrCreate(
                ['ujian_id' => $ujian->id, 'siswa_id' => $sk->siswa_id],
                [
                    'kelas_id' => $sk->kelas_id,
                    'status_kehadiran' => 'hadir',
                    'jumlah_benar' => 0,
                    'jumlah_salah' => 0,
                    'total_skor' => 0,
                    'nilai' => 0,
                ]
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\AssessmentTemplateExport;
use App\Models\Ujian;
use App\Models\Soal;
use App\Models\LogAktivitas;
use App\Imports\SpreadsheetRowsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class KunciJawabanController extends Controller
{
    public function index(Ujian $ujian)
    {
        $ujian->load('soal');
        return view('kunci_jawaban.index', compact('ujian'));
    }

    public function store(Request $request, Ujian $ujian)
    {
        $request->validate([
            'kunci' => 'required|array',
            'kunci.*' => 'nullable|in:A,B,C,D,E',
            'bobot' => 'nullable|array',
            'bobot.*' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $ujian) {
            foreach ($request->kunci as $soalId => $kunci) {
                $soal = Soal::where('id', $soalId)->where('ujian_id', $ujian->id)->first();
                if ($soal) {
                    $soal->update([
                        'kunci_jawaban' => $kunci ?: null,
                        'bobot' => $request->bobot[$soalId] ?? 1.00,
                    ]);
                }
            }

            // Update status ujian jika kunci sudah lengkap
            if ($ujian->fresh()->isKunciLengkap() && $ujian->status === 'draft') {
                $ujian->update(['status' => 'kunci_lengkap']);
            }

            LogAktivitas::catat("Menyimpan kunci jawaban ujian: {$ujian->nama_ujian}", 'Kunci Jawaban');
        });

        return redirect()->route('kunci-jawaban.index', $ujian)->with('success', 'Kunci jawaban berhasil disimpan.');
    }

    public function downloadTemplate(Ujian $ujian, ?string $filename = null)
    {
        LogAktivitas::catat('Download template kunci jawaban', 'Kunci Jawaban', "Download template kunci jawaban ujian: {$ujian->nama_ujian}");

        $filename = 'template_kunci_jawaban_' . str_replace(' ', '_', $ujian->nama_ujian) . '.xlsx';

        return Excel::download(new AssessmentTemplateExport('kunci-jawaban', $ujian), $filename);
    }

    public function import(Request $request, Ujian $ujian)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            if (in_array($extension, ['csv', 'txt'])) {
                $this->importFromCsv($file, $ujian);
            } else {
                // Use Laravel Excel for xlsx/xls
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $sheetNames = array_map(fn($name) => strtoupper(trim((string)$name)), $spreadsheet->getSheetNames());

                $data = \Maatwebsite\Excel\Facades\Excel::toArray(new SpreadsheetRowsImport(), $file);
                
                $rows = $this->selectImportSheetRows($data, $sheetNames);
                if (empty($rows)) {
                    throw new \Exception("Template tidak valid. Gunakan template resmi dari SIABSoal.");
                }

                // Remove header row if present
                $firstRow = $rows[0] ?? [];
                if (isset($firstRow[0]) && (strtolower(trim((string)$firstRow[0])) === 'nomor_soal' || strtolower(trim((string)$firstRow[0])) === 'nomor soal')) {
                    array_shift($rows);
                }

                $this->importFromArray($rows, $ujian);
            }

            LogAktivitas::catat(
                "Import template kunci jawaban ujian: {$ujian->nama_ujian}",
                'Kunci Jawaban',
                "Import template Excel kunci jawaban untuk ujian: {$ujian->nama_ujian}",
                Ujian::class,
                $ujian->id
            );

            return redirect()->route('kunci-jawaban.index', $ujian)->with('success', 'Kunci jawaban berhasil diimport.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    private function importFromCsv($file, Ujian $ujian)
    {
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($rows);
        $this->importFromArray($rows, $ujian, true);
    }

    private function importFromArray(array $rows, Ujian $ujian, $isCsv = false)
    {
        DB::transaction(function () use ($rows, $ujian, $isCsv) {
            foreach ($rows as $row) {
                $nomorSoal = $row[0] ?? null;
                $kunci = strtoupper(trim((string)($row[1] ?? '')));
                $bobot = $row[2] ?? 1;

                if (!$nomorSoal || !in_array($kunci, ['A','B','C','D','E',''])) continue;

                $soal = Soal::where('ujian_id', $ujian->id)->where('nomor_soal', (int)$nomorSoal)->first();
                if ($soal) {
                    $soal->update([
                        'kunci_jawaban' => $kunci ?: null,
                        'bobot' => (float)$bobot ?: 1.00,
                    ]);
                }
            }

            if ($ujian->fresh()->isKunciLengkap() && $ujian->status === 'draft') {
                $ujian->update(['status' => 'kunci_lengkap']);
            }
        });
    }

    private function selectImportSheetRows(array $sheets, array $sheetNames): array
    {
        // 1. Coba baca DATA_IMPORT_SYSTEM
        $importIdx = array_search('DATA_IMPORT_SYSTEM', $sheetNames);
        if ($importIdx !== false && isset($sheets[$importIdx])) {
            $rows = $this->filledRows($sheets[$importIdx]);
            $header = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $rows[0] ?? []
            );

            if (array_slice($header, 0, 3) === ['nomor_soal', 'kunci_jawaban', 'bobot']) {
                return $rows;
            }
        }

        // 2. Coba baca DATA_INPUT
        $inputIdx = array_search('DATA_INPUT', $sheetNames);
        if ($inputIdx !== false && isset($sheets[$inputIdx])) {
            $rows = $this->filledRows($sheets[$inputIdx]);
            $header = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $rows[0] ?? []
            );

            if (array_slice($header, 0, 3) === ['nomor_soal', 'kunci_jawaban', 'bobot']) {
                return $rows;
            }
        }

        return [];
    }

    private function filledRows(array $rows): array
    {
        return array_values(array_filter($rows, function ($row) {
            return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) > 0;
        }));
    }
}

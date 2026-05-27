<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\Soal;
use App\Models\LogAktivitas;
use App\Imports\SpreadsheetRowsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function downloadTemplate(Ujian $ujian)
    {
        // Return simple CSV template for kunci jawaban
        $headers = ['nomor_soal', 'kunci_jawaban', 'bobot'];
        $rows = [];
        for ($i = 1; $i <= $ujian->jumlah_soal; $i++) {
            $rows[] = [$i, '', '1'];
        }

        $callback = function() use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        $filename = 'template_kunci_jawaban_' . str_replace(' ', '_', $ujian->nama_ujian) . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
                $data = \Maatwebsite\Excel\Facades\Excel::toArray(new SpreadsheetRowsImport(), $file);
                $this->importFromArray($data[0] ?? [], $ujian);
            }

            LogAktivitas::catat("Import kunci jawaban ujian: {$ujian->nama_ujian}", 'Kunci Jawaban');

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
                $nomorSoal = $isCsv ? ($row[0] ?? null) : ($row[0] ?? null);
                $kunci = strtoupper(trim($row[1] ?? ''));
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
}

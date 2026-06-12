<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\LogAktivitas;
use App\Exports\AssessmentTemplateExport;
use App\Imports\SpreadsheetRowsImport;
use App\Services\SiswaImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $siswa = Siswa::when($request->search, fn($q, $s) => $q->where(function ($query) use ($s) {
                          $query->where('nama_siswa', 'like', "%{$s}%")
                              ->orWhere('nis', 'like', "%{$s}%")
                              ->orWhere('nisn', 'like', "%{$s}%");
                      }))
                      ->when($request->status, fn($q, $s) => $q->where('status', $s))
                      ->when(auth()->user()->isGuru(), function ($q) {
                          $kelasIds = auth()->user()->guru?->kelasWali()->pluck('id') ?? collect();
                          $q->whereHas('siswaKelas', fn($sk) => $sk->whereIn('kelas_id', $kelasIds));
                      })
                      ->orderBy('nama_siswa')
                      ->paginate(20)
                      ->withQueryString();

        return view('siswa.index', compact('siswa'));
    }

    public function create()
    {
        return view('siswa.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nis'            => 'required|string|max:20|unique:siswa,nis',
            'nisn'           => 'nullable|string|max:20|unique:siswa,nisn',
            'nama_siswa'     => 'required|string|max:255',
            'jenis_kelamin'  => 'required|in:L,P',
            'alamat'         => 'nullable|string',
            'status'         => 'required|in:aktif,nonaktif,lulus',
        ]);

        Siswa::create($request->only(['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'alamat', 'status']));

        LogAktivitas::catat("Menambahkan siswa: {$request->nama_siswa}", 'Siswa');

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Siswa $siswa)
    {
        $this->authorizeSiswaAccess($siswa);
        return view('siswa.edit', compact('siswa'));
    }

    public function update(Request $request, Siswa $siswa)
    {
        $this->authorizeSiswaAccess($siswa);

        $request->validate([
            'nis'            => 'required|string|max:20|unique:siswa,nis,' . $siswa->id,
            'nisn'           => 'nullable|string|max:20|unique:siswa,nisn,' . $siswa->id,
            'nama_siswa'     => 'required|string|max:255',
            'jenis_kelamin'  => 'required|in:L,P',
            'alamat'         => 'nullable|string',
            'status'         => 'required|in:aktif,nonaktif,lulus',
        ]);

        $siswa->update($request->only(['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'alamat', 'status']));

        LogAktivitas::catat("Mengubah siswa: {$siswa->nama_siswa}", 'Siswa');

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $this->authorizeSiswaAccess($siswa);

        if ($siswa->pesertaUjian()->exists()) {
            return back()->with('error', 'Siswa tidak dapat dihapus karena memiliki data ujian.');
        }

        $nama = $siswa->nama_siswa;
        $siswa->delete();

        LogAktivitas::catat("Menghapus siswa: {$nama}", 'Siswa');

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    private function authorizeSiswaAccess(Siswa $siswa): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        $kelasIds = $user->guru?->kelasWali()->pluck('id') ?? collect();
        $hasAccess = $siswa->siswaKelas()->whereIn('kelas_id', $kelasIds)->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke data siswa ini.');
        }
    }

    /**
     * Download template Excel untuk data siswa.
     */
    public function downloadTemplate(?string $filename = null)
    {
        LogAktivitas::catat('Download template Siswa', 'Siswa', 'Download template data siswa');
        return Excel::download(new AssessmentTemplateExport('data-siswa'), 'template_data_siswa.xlsx');
    }

    /**
     * Preview hasil import data siswa.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            if (in_array($extension, ['csv', 'txt'])) {
                $rows = array_map('str_getcsv', file($file->getRealPath()));
                $rows = $this->filledRows($rows);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $sheetNames = array_map(fn($name) => strtoupper(trim((string)$name)), $spreadsheet->getSheetNames());
                $sheets = Excel::toArray(new SpreadsheetRowsImport(), $file);

                $rows = [];
                // 1. Coba baca DATA_IMPORT_SYSTEM
                $importIdx = array_search('DATA_IMPORT_SYSTEM', $sheetNames);
                if ($importIdx !== false && isset($sheets[$importIdx])) {
                    $rows = $this->filledRows($sheets[$importIdx]);
                }
                
                // 2. Coba baca DATA_INPUT
                if (count($rows) <= 1) {
                    $inputIdx = array_search('DATA_INPUT', $sheetNames);
                    if ($inputIdx !== false && isset($sheets[$inputIdx])) {
                        $rows = $this->filledRows($sheets[$inputIdx]);
                    }
                }

                // 3. Fallback sheet pertama
                if (count($rows) <= 1 && isset($sheets[0])) {
                    $rows = $this->filledRows($sheets[0]);
                }
            }

            $header = array_shift($rows);
            $service = new SiswaImportService();
            if (!$header || !$service->isHeaderValid($header)) {
                $expected = implode(', ', $service->expectedHeader());
                $received = $header ? implode(', ', $service->normalizeHeader($header)) : '-';
                return back()->with('error', "Format header tidak sesuai. Header yang benar: {$expected}. Header file: {$received}.");
            }

            $result = $service->preview($rows);

            session([
                'siswa_import_preview_data' => $result['valid'],
                'siswa_import_error_count' => $result['summary']['error_count'],
            ]);

            return view('siswa.preview', [
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal preview: ' . $e->getMessage());
        }
    }

    /**
     * Konfirmasi dan simpan data siswa hasil import.
     */
    public function confirmImport(Request $request)
    {
        $rows = session('siswa_import_preview_data');
        if (!$rows) {
            return back()->with('error', 'Session import sudah kadaluarsa. Silakan upload ulang.');
        }

        if ((int) session('siswa_import_error_count', 0) > 0) {
            return redirect()->route('siswa.index')
                ->with('error', 'Import belum bisa dikonfirmasi karena masih ada data error. Perbaiki file lalu upload ulang.');
        }

        try {
            $service = new SiswaImportService();
            $imported = $service->importValidated($rows);

            session()->forget(['siswa_import_preview_data', 'siswa_import_error_count']);

            LogAktivitas::catat("Import siswa: {$imported} siswa", 'Siswa');

            return redirect()->route('siswa.index')
                ->with('success', "Import berhasil. {$imported} data siswa disimpan.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    private function filledRows(array $rows): array
    {
        return array_values(array_filter($rows, function ($row) {
            return !empty(array_filter($row, fn($val) => $val !== null && trim((string)$val) !== ''));
        }));
    }
}

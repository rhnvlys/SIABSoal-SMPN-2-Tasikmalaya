<?php

namespace App\Http\Controllers;

use App\Exports\AssessmentTemplateExport;
use App\Models\LogAktivitas;
use App\Models\Ujian;
use App\Services\CompleteTemplateImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TemplateExcelController extends Controller
{
    public function __construct(private readonly CompleteTemplateImportService $completeTemplateImportService)
    {
    }

    public function index(Request $request)
    {
        $ujianList = $this->accessibleUjianQuery()
            ->with(['mapel', 'kelas', 'tahunAjaran'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $selectedUjian = null;
        if ($request->filled('ujian_id')) {
            $selectedUjian = $this->accessibleUjianQuery()
                ->with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
                ->whereKey($request->integer('ujian_id'))
                ->first();

            if (!$selectedUjian) {
                return redirect()->route('template-excel.index')
                    ->with('error', 'Ujian tidak ditemukan atau Anda tidak memiliki akses ke ujian ini.');
            }
        }

        $templates = [
            [
                'type' => 'lengkap',
                'title' => 'Template Excel Lengkap',
                'description' => 'Satu workbook administrasi penilaian lengkap: identitas, data siswa, kunci, input jawaban/skor, T1 sampai T5.',
                'icon' => 'bi-file-earmark-spreadsheet-fill',
                'requires_ujian' => true,
                'featured' => true,
                'sheet_summary' => '16 sheet: Identitas, Data Kelas, Data Siswa, TP/LM/KKTP, Daftar Hadir, Kunci Jawaban, Input Jawaban, Input Skor, T1-T5, Referensi.',
            ],
            [
                'type' => 'data-siswa',
                'title' => 'Template Data Siswa',
                'description' => 'Format dasar NIS, NISN, nama siswa, jenis kelamin, kelas, tahun ajaran, dan status.',
                'icon' => 'bi-mortarboard-fill',
                'requires_ujian' => false,
                'sheet_summary' => 'Format khusus data siswa dan sheet teknis import.',
            ],
            [
                'type' => 'kunci-jawaban',
                'title' => 'Template Kunci Jawaban',
                'description' => 'Format nomor soal, kunci A/B/C/D/E, dan bobot.',
                'icon' => 'bi-key-fill',
                'requires_ujian' => true,
                'sheet_summary' => 'Format khusus kunci jawaban dan bobot soal.',
            ],
            [
                'type' => 'jawaban-abcd',
                'title' => 'Template Jawaban A/B/C/D/E',
                'description' => 'Format import jawaban siswa yang akan diproses menjadi skor 0/1.',
                'icon' => 'bi-ui-checks-grid',
                'requires_ujian' => true,
                'sheet_summary' => 'Format khusus input jawaban A/B/C/D/E.',
            ],
            [
                'type' => 'skor-01',
                'title' => 'Template Skor 0/1',
                'description' => 'Format import skor biner jika guru sudah memiliki hasil benar/salah.',
                'icon' => 'bi-123',
                'requires_ujian' => true,
                'sheet_summary' => 'Format khusus input skor benar/salah 0/1.',
            ],
            [
                'type' => 'daftar-nilai',
                'title' => 'Template Daftar Nilai',
                'description' => 'Format daftar nilai T4 yang mudah dibaca guru.',
                'icon' => 'bi-journal-text',
                'requires_ujian' => false,
                'sheet_summary' => 'Format hasil daftar nilai T4.',
            ],
            [
                'type' => 'rekap-nilai',
                'title' => 'Template Rekap Nilai',
                'description' => 'Format ringkasan T5 untuk rekap kehadiran, nilai, ketuntasan, dan kualitas soal.',
                'icon' => 'bi-file-earmark-bar-graph-fill',
                'requires_ujian' => false,
                'sheet_summary' => 'Format hasil rekap nilai T5.',
            ],
        ];

        $riwayatUpload = LogAktivitas::query()
            ->where('modul', 'Template Excel')
            ->where(function ($query) {
                $query->where('aksi', 'like', '%Upload template lengkap%')
                    ->orWhere('aksi', 'like', '%Preview upload template lengkap%')
                    ->orWhere('aksi', 'like', '%Import %')
                    ->orWhere('aksi', 'like', '%Sheet diabaikan%');
            })
            ->when(auth()->user()->isGuru(), fn ($query) => $query->where('user_id', auth()->id()))
            ->latest()
            ->limit(10)
            ->get();

        return view('template_excel.index', compact('templates', 'ujianList', 'selectedUjian', 'riwayatUpload'));
    }

    public function download(Request $request, string $type, ?string $filename = null)
    {
        abort_unless(array_key_exists($type, AssessmentTemplateExport::TYPES), 404);

        $ujian = null;
        if ($request->filled('ujian_id')) {
            $ujian = $this->accessibleUjianQuery()
                ->with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
                ->whereKey($request->integer('ujian_id'))
                ->first();

            if (!$ujian) {
                // Jangan bocorkan keberadaan ujian milik guru lain melalui endpoint download.
                abort(404);
            }
        }

        if (in_array($type, ['lengkap', 'kunci-jawaban', 'jawaban-abcd', 'skor-01'], true) && !$ujian) {
            return redirect()->route('template-excel.index')
                ->with('error', 'Pilih ujian terlebih dahulu untuk download template ini.');
        }

        $label = AssessmentTemplateExport::label($type);
        $suffix = $ujian ? '_' . str_replace(' ', '_', strtolower($ujian->nama_ujian)) : '';
        $filename = $type === 'lengkap'
            ? 'Template_Administrasi_Penilaian_SIABSoal.xlsx'
            : str_replace('-', '_', $type) . $suffix . '.xlsx';

        LogAktivitas::catat(
            "Download template Excel: {$label}",
            'Template Excel',
            $ujian
                ? "Download {$label} untuk ujian: {$ujian->nama_ujian}"
                : "Download {$label}",
            $ujian ? Ujian::class : null,
            $ujian?->id
        );

        return Excel::download(new AssessmentTemplateExport($type, $ujian), $filename);
    }

    public function previewUpload(Request $request)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isGuru(), 403);

        $request->validate(['ujian_id' => 'required|integer']);

        $ujian = $this->accessibleUjianQuery()
            ->with(['guru', 'mapel', 'kelas', 'tahunAjaran', 'soal'])
            ->whereKey($request->integer('ujian_id'))
            ->first();

        if (!$ujian) {
            return redirect()->route('template-excel.index')
                ->with('error', 'Ujian tidak ditemukan atau Anda tidak memiliki akses ke ujian ini.');
        }

        $request->validate([
            'import_mode' => 'nullable|in:skor-01,jawaban-abcd',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $result = $this->completeTemplateImportService->preview(
            $request->file('file'),
            auth()->user(),
            $ujian,
            $request->string('import_mode')->toString()
        );

        session([
            'template_lengkap_preview' => [
                'ujian_id' => $ujian->id,
                'payload' => $result['payload'],
                'summary' => $result['summary'],
                'found_sheets' => $result['found_sheets'],
                'processable_sheets' => $result['processable_sheets'],
                'ignored_sheets' => $result['ignored_sheets'],
                'row_counts' => $result['row_counts'],
                'warnings' => $result['warnings'],
                'errors' => $result['errors'],
                'can_process' => $result['can_process'],
            ],
        ]);

        LogAktivitas::catat(
            "Preview upload template lengkap: {$result['file_name']}",
            'Template Excel',
            "Preview upload template lengkap untuk ujian: {$ujian->nama_ujian}",
            Ujian::class,
            $ujian->id
        );

        foreach ($result['ignored_sheets'] as $sheet => $reason) {
            LogAktivitas::catat(
                "Sheet diabaikan: {$sheet}",
                'Template Excel',
                $reason,
                Ujian::class,
                $ujian->id
            );
        }

        foreach ($result['errors'] as $error) {
            LogAktivitas::catat(
                'Error validasi upload template lengkap',
                'Template Excel',
                $error,
                Ujian::class,
                $ujian->id
            );
        }

        return view('template_excel.preview', [
            'ujian' => $ujian,
            'result' => $result,
        ]);
    }

    public function confirmUpload(Request $request)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isGuru(), 403);

        $preview = session('template_lengkap_preview');
        if (!$preview || empty($preview['ujian_id'])) {
            return redirect()->route('template-excel.index')
                ->with('error', 'Session import sudah kadaluarsa. Silakan upload ulang.');
        }

        if (!empty($preview['errors'])) {
            return redirect()->route('template-excel.index', ['ujian_id' => $preview['ujian_id']])
                ->with('error', 'Import belum bisa dikonfirmasi karena masih ada error validasi.');
        }

        $ujian = $this->accessibleUjianQuery()
            ->with(['guru', 'mapel', 'kelas', 'tahunAjaran', 'soal'])
            ->whereKey((int) $preview['ujian_id'])
            ->first();

        if (!$ujian) {
            session()->forget('template_lengkap_preview');

            return redirect()->route('template-excel.index')
                ->with('error', 'Ujian tidak ditemukan atau Anda tidak memiliki akses ke ujian ini.');
        }

        $counts = $this->completeTemplateImportService->import($preview['payload'] ?? [], auth()->user(), $ujian);
        session()->forget('template_lengkap_preview');

        foreach ($counts as $sheet => $count) {
            LogAktivitas::catat(
                "Import {$sheet}: {$count} baris",
                'Template Excel',
                "Import {$sheet} dari template lengkap untuk ujian: {$ujian->nama_ujian}",
                Ujian::class,
                $ujian->id
            );
        }

        LogAktivitas::catat(
            "Upload template lengkap ujian: {$ujian->nama_ujian}",
            'Template Excel',
            'Upload template lengkap berhasil diproses.',
            Ujian::class,
            $ujian->id
        );

        return redirect()->route('data-mentah.index', $ujian)
            ->with('success', 'Import template lengkap berhasil diproses. Sheet diproses: ' . implode(', ', array_keys($counts)));
    }

    private function accessibleUjianQuery()
    {
        $query = Ujian::query();
        $user = auth()->user();

        if ($user->isGuru()) {
            if (!$user->guru) {
                abort(403, 'Akun guru belum terhubung dengan data guru.');
            }

            $query->where('guru_id', $user->guru->id);
        }

        return $query;
    }
}

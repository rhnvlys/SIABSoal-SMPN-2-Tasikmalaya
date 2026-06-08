<?php

namespace App\Http\Controllers;

use App\Exports\AssessmentTemplateExport;
use App\Models\LogAktivitas;
use App\Models\Ujian;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TemplateExcelController extends Controller
{
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
        }

        $templates = [
            [
                'type' => 'data-siswa',
                'title' => 'Template Data Siswa',
                'description' => 'Format dasar NIS, NISN, nama siswa, jenis kelamin, kelas, tahun ajaran, dan status.',
                'icon' => 'bi-mortarboard-fill',
                'requires_ujian' => false,
            ],
            [
                'type' => 'kunci-jawaban',
                'title' => 'Template Kunci Jawaban',
                'description' => 'Format nomor soal, kunci A/B/C/D/E, dan bobot.',
                'icon' => 'bi-key-fill',
                'requires_ujian' => true,
            ],
            [
                'type' => 'jawaban-abcd',
                'title' => 'Template Jawaban A/B/C/D/E',
                'description' => 'Format import jawaban siswa yang akan diproses menjadi skor 0/1.',
                'icon' => 'bi-ui-checks-grid',
                'requires_ujian' => true,
            ],
            [
                'type' => 'skor-01',
                'title' => 'Template Skor 0/1',
                'description' => 'Format import skor biner jika guru sudah memiliki hasil benar/salah.',
                'icon' => 'bi-123',
                'requires_ujian' => true,
            ],
            [
                'type' => 'daftar-nilai',
                'title' => 'Template Daftar Nilai',
                'description' => 'Format daftar nilai T4 yang mudah dibaca guru.',
                'icon' => 'bi-journal-text',
                'requires_ujian' => false,
            ],
            [
                'type' => 'rekap-nilai',
                'title' => 'Template Rekap Nilai',
                'description' => 'Format ringkasan T5 untuk rekap kehadiran, nilai, ketuntasan, dan kualitas soal.',
                'icon' => 'bi-file-earmark-bar-graph-fill',
                'requires_ujian' => false,
            ],
        ];

        return view('template_excel.index', compact('templates', 'ujianList', 'selectedUjian'));
    }

    public function download(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, AssessmentTemplateExport::TYPES), 404);

        $ujian = null;
        if ($request->filled('ujian_id')) {
            $ujian = $this->accessibleUjianQuery()
                ->with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
                ->whereKey($request->integer('ujian_id'))
                ->firstOrFail();
        }

        if (in_array($type, ['kunci-jawaban', 'jawaban-abcd', 'skor-01'], true) && !$ujian) {
            return redirect()->route('template-excel.index')
                ->with('error', 'Pilih ujian terlebih dahulu untuk download template ini.');
        }

        $label = AssessmentTemplateExport::label($type);
        $suffix = $ujian ? '_' . str_replace(' ', '_', strtolower($ujian->nama_ujian)) : '';
        $filename = str_replace('-', '_', $type) . $suffix . '.xlsx';

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

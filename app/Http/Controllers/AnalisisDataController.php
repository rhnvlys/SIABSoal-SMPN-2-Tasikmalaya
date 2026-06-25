<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\AnalisisButir;
use App\Models\LogAktivitas;
use App\Services\ItemAnalysisService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class AnalisisDataController extends Controller
{
    protected $analysisService;
    protected $reportService;

    public function __construct(ItemAnalysisService $analysisService, ReportService $reportService)
    {
        $this->analysisService = $analysisService;
        $this->reportService = $reportService;
    }

    /**
     * Tampilkan halaman Analisis Data T3
     */
    public function index(Ujian $ujian)
    {
        $ujian->load(['soal', 'guru', 'mapel']);
        $analisis = AnalisisButir::where('ujian_id', $ujian->id)
            ->orderBy('nomor_soal')
            ->paginate(20)
            ->withQueryString();

        $ringkasan = $this->reportService->getRingkasanAnalisis($ujian->id);

        return view('analisis_data.index', compact('ujian', 'analisis', 'ringkasan'));
    }

    /**
     * Proses analisis butir soal
     */
    public function proses(Request $request, Ujian $ujian)
    {
        try {
            $hasil = $this->analysisService->prosesAnalisis($ujian->id);

            LogAktivitas::catat("Memproses Analisis Data T3 ujian: {$ujian->nama_ujian}", 'Analisis Data');

            return redirect()->route('analisis-data.index', $ujian)
                             ->with('success', 'Analisis Data T3 berhasil diproses. ' . count($hasil) . ' soal dianalisis.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

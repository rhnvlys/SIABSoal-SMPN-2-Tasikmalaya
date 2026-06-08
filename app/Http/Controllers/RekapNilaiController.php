<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Ujian;
use App\Services\ReportService;

class RekapNilaiController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Tampilkan halaman Rekap Nilai T5
     */
    public function index(Ujian $ujian)
    {
        $data = $this->reportService->getRekapNilai($ujian->id);

        LogAktivitas::catat(
            "Melihat Rekap Nilai T5 ujian: {$ujian->nama_ujian}",
            'Rekap Nilai',
            "Melihat Rekap Nilai T5 untuk ujian: {$ujian->nama_ujian}",
            Ujian::class,
            $ujian->id
        );

        return view('rekap_nilai.index', $data);
    }
}

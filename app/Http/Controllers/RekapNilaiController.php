<?php

namespace App\Http\Controllers;

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

        return view('rekap_nilai.index', $data);
    }
}

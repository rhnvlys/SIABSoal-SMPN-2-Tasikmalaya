<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Ujian;
use App\Models\AnalisisButir;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Statistik utama
        $stats = [
            'siswa'    => Siswa::where('status', 'aktif')->count(),
            'guru'     => Guru::where('status', 'aktif')->count(),
            'kelas'    => Kelas::count(),
            'mapel'    => Mapel::count(),
            'ujian'    => Ujian::count(),
        ];

        // Statistik analisis soal — hanya DP & TK sesuai arahan sekolah
        $analisis = AnalisisButir::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN kategori_dp = 'Baik' THEN 1 ELSE 0 END) as soal_baik,
            SUM(CASE WHEN keputusan = 'Revisi' THEN 1 ELSE 0 END) as soal_revisi,
            SUM(CASE WHEN keputusan = 'Buang' THEN 1 ELSE 0 END) as soal_buang,
            SUM(CASE WHEN kategori_tk = 'Mudah' THEN 1 ELSE 0 END) as soal_mudah,
            SUM(CASE WHEN kategori_tk = 'Sedang' THEN 1 ELSE 0 END) as soal_sedang,
            SUM(CASE WHEN kategori_tk = 'Sukar' THEN 1 ELSE 0 END) as soal_sukar
        ")->first();

        // Status progres ujian
        $statusProgres = Ujian::selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Ujian terbaru
        $ujianQuery = Ujian::with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
                           ->orderBy('created_at', 'desc');

        // Guru hanya lihat ujian miliknya
        if ($user->hasRole('Guru') && $user->guru) {
            $ujianQuery->where('guru_id', $user->guru->id);
        }

        $ujianTerbaru = $ujianQuery->limit(5)->get();

        return view('dashboard.index', compact('stats', 'analisis', 'statusProgres', 'ujianTerbaru'));
    }
}

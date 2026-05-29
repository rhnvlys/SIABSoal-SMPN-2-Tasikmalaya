<?php

namespace App\Http\Controllers;

use App\Models\AnalisisButir;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $routeName = auth()->user()->dashboardRouteName();
        if (!$routeName) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Role pengguna tidak valid. Hubungi administrator.');
        }

        return redirect()->route($routeName);
    }

    public function admin()
    {
        $stats = [
            'siswa' => Siswa::where('status', 'aktif')->count(),
            'guru' => Guru::where('status', 'aktif')->count(),
            'kelas' => Kelas::count(),
            'mapel' => Mapel::count(),
            'ujian' => Ujian::count(),
        ];

        $analisis = $this->analisisSummary();
        $statusProgres = Ujian::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        $ujianTerbaru = Ujian::with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.admin', compact('stats', 'analisis', 'statusProgres', 'ujianTerbaru'));
    }

    public function guru()
    {
        $guru = auth()->user()->guru;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung dengan data guru.');
        }

        $ujianIds = Ujian::where('guru_id', $guru->id)->pluck('id');
        $kelasWali = Kelas::with('tahunAjaran')
            ->withCount('siswaKelas')
            ->where('wali_kelas_id', $guru->id)
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        $stats = [
            'ujian' => $ujianIds->count(),
            'kelas_wali' => $kelasWali->count(),
            'siswa_wali' => SiswaKelas::whereIn('kelas_id', $kelasWali->pluck('id'))->distinct('siswa_id')->count('siswa_id'),
            't1' => Ujian::where('guru_id', $guru->id)->whereIn('status', ['data_mentah', 'olah_data', 'dianalisis', 'selesai'])->count(),
            't2' => Ujian::where('guru_id', $guru->id)->whereIn('status', ['olah_data', 'dianalisis', 'selesai'])->count(),
            't3' => Ujian::where('guru_id', $guru->id)->whereIn('status', ['dianalisis', 'selesai'])->count(),
        ];

        $analisis = $this->analisisSummary($ujianIds);
        $ujianTerbaru = Ujian::with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
            ->where('guru_id', $guru->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.guru', compact('stats', 'analisis', 'ujianTerbaru', 'kelasWali'));
    }

    public function kepalaSekolah()
    {
        $stats = [
            'guru' => Guru::where('status', 'aktif')->count(),
            'siswa' => Siswa::where('status', 'aktif')->count(),
            'kelas' => Kelas::count(),
            'mapel' => Mapel::count(),
            'ujian_selesai' => Ujian::where('status', 'selesai')->count(),
            'ujian_belum_selesai' => Ujian::where('status', '!=', 'selesai')->count(),
        ];

        $analisis = $this->analisisSummary();
        $ujianTerbaru = Ujian::with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $rataNilaiUjian = Ujian::with(['guru', 'mapel', 'kelas'])
            ->withAvg('pesertaUjian as rata_rata_nilai', 'nilai')
            ->whereHas('pesertaUjian')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $laporanTerbaru = Ujian::with(['guru', 'mapel', 'kelas'])
            ->whereIn('status', ['dianalisis', 'selesai'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard.kepala-sekolah', compact(
            'stats',
            'analisis',
            'ujianTerbaru',
            'rataNilaiUjian',
            'laporanTerbaru'
        ));
    }

    private function analisisSummary(?Collection $ujianIds = null)
    {
        $query = AnalisisButir::query();
        if ($ujianIds !== null) {
            $query->whereIn('ujian_id', $ujianIds);
        }

        return $query->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN kategori_dp = 'Baik' THEN 1 ELSE 0 END) as soal_baik,
            SUM(CASE WHEN keputusan = 'Revisi' THEN 1 ELSE 0 END) as soal_revisi,
            SUM(CASE WHEN keputusan = 'Buang' THEN 1 ELSE 0 END) as soal_buang,
            SUM(CASE WHEN kategori_tk = 'Mudah' THEN 1 ELSE 0 END) as soal_mudah,
            SUM(CASE WHEN kategori_tk = 'Sedang' THEN 1 ELSE 0 END) as soal_sedang,
            SUM(CASE WHEN kategori_tk = 'Sukar' THEN 1 ELSE 0 END) as soal_sukar
        ")->first();
    }
}

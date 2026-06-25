<?php

namespace App\Http\Controllers;

use App\Models\AnalisisButir;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\JawabanSiswa;
use App\Models\LogAktivitas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use App\Models\UjianKelas;
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
        $aktivitasTerbaru = LogAktivitas::latest()->limit(6)->get();

        return view('dashboard.admin', compact('stats', 'analisis', 'statusProgres', 'ujianTerbaru', 'aktivitasTerbaru'));
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

        $kelasUjianIds = UjianKelas::whereIn('ujian_id', $ujianIds)
            ->pluck('kelas_id');
        $kelasSayaIds = $kelasWali->pluck('id')->merge($kelasUjianIds)->unique()->values();
        $ujianLanjutkan = Ujian::with(['mapel', 'kelas', 'tahunAjaran'])
            ->where('guru_id', $guru->id)
            ->orderByRaw("CASE WHEN status = 'selesai' THEN 1 ELSE 0 END")
            ->orderByDesc('updated_at')
            ->first();

        $stats = [
            'ujian_saya' => $ujianIds->count(),
            'kelas_saya' => $kelasSayaIds->count(),
            'siswa_kelas_saya' => SiswaKelas::whereIn('kelas_id', $kelasSayaIds)->distinct('siswa_id')->count('siswa_id'),
            'belum_lengkap' => Ujian::where('guru_id', $guru->id)
                ->whereNotIn('status', ['dianalisis', 'selesai'])
                ->count(),
            'sudah_dianalisis' => Ujian::where('guru_id', $guru->id)
                ->whereIn('status', ['dianalisis', 'selesai'])
                ->count(),
            'laporan_tersedia' => Ujian::where('guru_id', $guru->id)
                ->whereIn('status', ['data_mentah', 'olah_data', 'dianalisis', 'selesai'])
                ->count(),
        ];

        $analisis = $this->analisisSummary($ujianIds);
        $ujianTerbaru = Ujian::with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
            ->where('guru_id', $guru->id)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(function (Ujian $ujian) {
                $ujian->next_action = $this->nextWorkflowAction($ujian);
                return $ujian;
            });
        $quickFlow = $this->quickFlow($ujianLanjutkan);
        $aktivitasSaya = LogAktivitas::where('user_id', auth()->id())->latest()->limit(6)->get();

        return view('dashboard.guru', compact('stats', 'analisis', 'ujianTerbaru', 'kelasWali', 'aktivitasSaya', 'ujianLanjutkan', 'quickFlow'));
    }

    public function kepalaSekolah()
    {
        $stats = [
            'guru' => Guru::where('status', 'aktif')->count(),
            'siswa' => Siswa::where('status', 'aktif')->count(),
            'kelas' => Kelas::count(),
            'mapel' => Mapel::count(),
            'ujian' => Ujian::count(),
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
        $aktivitasTerbaru = LogAktivitas::latest()->limit(6)->get();

        return view('dashboard.kepala-sekolah', compact(
            'stats',
            'analisis',
            'ujianTerbaru',
            'rataNilaiUjian',
            'laporanTerbaru',
            'aktivitasTerbaru'
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

    private function quickFlow(?Ujian $ujian): array
    {
        $hasUjian = $ujian !== null;
        $status = $ujian?->status;
        $hasJawaban = $hasUjian && JawabanSiswa::whereHas('pesertaUjian', fn ($query) => $query
            ->where('ujian_id', $ujian->id))->exists();

        return [
            ['label' => 'Buat Ujian', 'route' => 'ujian.create', 'params' => [], 'done' => $hasUjian, 'enabled' => true],
            ['label' => 'Isi Kunci Jawaban', 'route' => $hasUjian ? 'kunci-jawaban.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => $hasUjian && $ujian->isKunciLengkap(), 'enabled' => $hasUjian],
            ['label' => 'Download Template Excel', 'route' => 'template-excel.index', 'params' => $hasUjian ? ['ujian_id' => $ujian->id] : [], 'done' => $hasUjian, 'enabled' => $hasUjian],
            ['label' => 'Import Jawaban', 'route' => $hasUjian ? 'data-mentah.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => $hasJawaban, 'enabled' => $hasUjian],
            ['label' => 'Proses Data Mentah T1', 'route' => $hasUjian ? 'data-mentah.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => in_array($status, ['data_mentah', 'olah_data', 'dianalisis', 'selesai'], true), 'enabled' => $hasUjian],
            ['label' => 'Proses Olah Data T2', 'route' => $hasUjian ? 'olah-data.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => in_array($status, ['olah_data', 'dianalisis', 'selesai'], true), 'enabled' => $hasUjian],
            ['label' => 'Proses Analisis Data T3', 'route' => $hasUjian ? 'analisis-data.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => in_array($status, ['dianalisis', 'selesai'], true), 'enabled' => $hasUjian],
            ['label' => 'Lihat Daftar Nilai T4', 'route' => $hasUjian ? 'daftar-nilai.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => in_array($status, ['data_mentah', 'olah_data', 'dianalisis', 'selesai'], true), 'enabled' => $hasUjian],
            ['label' => 'Lihat Rekap Nilai T5', 'route' => $hasUjian ? 'rekap-nilai.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => in_array($status, ['dianalisis', 'selesai'], true), 'enabled' => $hasUjian],
            ['label' => 'Export Laporan', 'route' => $hasUjian ? 'rekap-nilai.index' : 'ujian.index', 'params' => $hasUjian ? [$ujian] : [], 'done' => in_array($status, ['dianalisis', 'selesai'], true), 'enabled' => $hasUjian],
        ];
    }

    private function nextWorkflowAction(Ujian $ujian): array
    {
        return match ($ujian->status) {
            'draft' => ['label' => 'Isi Kunci', 'route' => 'kunci-jawaban.index', 'params' => [$ujian]],
            'kunci_lengkap' => ['label' => 'Import Jawaban', 'route' => 'data-mentah.index', 'params' => [$ujian]],
            'data_mentah' => ['label' => 'Proses T2', 'route' => 'olah-data.index', 'params' => [$ujian]],
            'olah_data' => ['label' => 'Proses T3', 'route' => 'analisis-data.index', 'params' => [$ujian]],
            'dianalisis' => ['label' => 'Lihat T5', 'route' => 'rekap-nilai.index', 'params' => [$ujian]],
            'selesai' => ['label' => 'Export', 'route' => 'rekap-nilai.index', 'params' => [$ujian]],
            default => ['label' => 'Lanjutkan', 'route' => 'ujian.show', 'params' => [$ujian]],
        };
    }
}

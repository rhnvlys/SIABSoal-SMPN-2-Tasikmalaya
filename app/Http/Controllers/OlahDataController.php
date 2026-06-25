<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\LogAktivitas;
use App\Services\GroupingService;
use Illuminate\Http\Request;

class OlahDataController extends Controller
{
    protected $groupingService;

    public function __construct(GroupingService $groupingService)
    {
        $this->groupingService = $groupingService;
    }

    /**
     * Tampilkan halaman Olah Data T2
     */
    public function index(Ujian $ujian)
    {
        $ujian->load(['guru', 'mapel', 'tahunAjaran', 'kelas']);

        $statistik = PesertaUjian::where('ujian_id', $ujian->id)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status_kehadiran = 'hadir' THEN 1 ELSE 0 END) AS hadir")
            ->selectRaw("SUM(CASE WHEN status_kehadiran = 'tidak_hadir' THEN 1 ELSE 0 END) AS tidak_hadir")
            ->selectRaw("SUM(CASE WHEN kelompok = 'atas' THEN 1 ELSE 0 END) AS kelompok_atas")
            ->selectRaw("SUM(CASE WHEN kelompok = 'bawah' THEN 1 ELSE 0 END) AS kelompok_bawah")
            ->selectRaw("SUM(CASE WHEN kelompok = 'tengah' THEN 1 ELSE 0 END) AS kelompok_tengah")
            ->first();

        // Ambil peserta hadir diurutkan ranking
        $pesertaHadir = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('status_kehadiran', 'hadir')
            ->with('siswa:id,nis,nisn,nama_siswa,jenis_kelamin')
            ->orderBy('ranking')
            ->paginate(20, ['peserta_ujian.*'], 'hadir_page')
            ->withQueryString();

        $pesertaTidakHadir = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('status_kehadiran', 'tidak_hadir')
            ->with('siswa:id,nis,nama_siswa,jenis_kelamin')
            ->orderBy('id')
            ->paginate(20, ['peserta_ujian.*'], 'tidak_hadir_page')
            ->withQueryString();

        return view('olah_data.index', compact('ujian', 'pesertaHadir', 'pesertaTidakHadir', 'statistik'));
    }

    /**
     * Proses pengelompokan atas/bawah
     */
    public function proses(Request $request, Ujian $ujian)
    {
        $request->validate([
            'metode_kelompok' => 'required|in:persen_50,manual',
            'jumlah_kelompok_manual' => 'nullable|required_if:metode_kelompok,manual|integer|min:1',
        ]);

        try {
            $jumlahHadir = PesertaUjian::where('ujian_id', $ujian->id)
                ->where('status_kehadiran', 'hadir')
                ->count();

            $this->groupingService->hitungJumlahKelompok(
                $jumlahHadir,
                $request->input('metode_kelompok'),
                $request->input('metode_kelompok') === 'manual'
                    ? $request->integer('jumlah_kelompok_manual')
                    : null
            );

            $ujian->update([
                'metode_kelompok' => $request->input('metode_kelompok'),
                'jumlah_kelompok_manual' => $request->input('metode_kelompok') === 'manual'
                    ? $request->integer('jumlah_kelompok_manual')
                    : null,
            ]);

            $hasil = $this->groupingService->prosesKelompok($ujian->id);

            LogAktivitas::catat("Memproses Olah Data T2 ujian: {$ujian->nama_ujian}", 'Olah Data');

            $message = "Olah Data T2 berhasil diproses. Jumlah hadir: {$hasil['jumlah_hadir']}, " .
                       "Kelompok per grup: {$hasil['jumlah_kelompok']} siswa.";

            $redirect = redirect()->route('olah-data.index', $ujian)->with('success', $message);

            if ($hasil['warning']) {
                $redirect->with('warning', $hasil['warning']);
            }

            return $redirect;
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

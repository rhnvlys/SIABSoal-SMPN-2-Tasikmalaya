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
        $ujian->load(['soal', 'pesertaUjian.siswa', 'pesertaUjian.jawabanSiswa']);

        // Ambil peserta hadir diurutkan ranking
        $pesertaHadir = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('status_kehadiran', 'hadir')
            ->with(['siswa', 'jawabanSiswa'])
            ->orderBy('ranking')
            ->get();

        $pesertaTidakHadir = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('status_kehadiran', 'tidak_hadir')
            ->with('siswa')
            ->get();

        // Statistik kelompok
        $kelAtas  = $pesertaHadir->where('kelompok', 'atas');
        $kelBawah = $pesertaHadir->where('kelompok', 'bawah');
        $kelTengah = $pesertaHadir->where('kelompok', 'tengah');

        return view('olah_data.index', compact('ujian', 'pesertaHadir', 'pesertaTidakHadir', 'kelAtas', 'kelBawah', 'kelTengah'));
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

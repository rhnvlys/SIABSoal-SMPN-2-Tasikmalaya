<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\LogAktivitas;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarNilaiController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Tampilkan halaman Daftar Nilai T4
     */
    public function index(Ujian $ujian)
    {
        $data = $this->reportService->getDaftarNilai($ujian->id, 20);

        LogAktivitas::catat(
            "Melihat Daftar Nilai T4 ujian: {$ujian->nama_ujian}",
            'Daftar Nilai',
            "Melihat Daftar Nilai T4 untuk ujian: {$ujian->nama_ujian}",
            Ujian::class,
            $ujian->id
        );

        return view('daftar_nilai.index', $data);
    }

    /**
     * Simpan nilai manual jika guru perlu mengisi daftar nilai tanpa hasil T1.
     */
    public function updateManual(Request $request, Ujian $ujian)
    {
        $request->validate([
            'nilai' => 'required|array',
            'status_kehadiran' => 'required|array',
        ]);

        try {
            DB::transaction(function () use ($request, $ujian) {
                foreach ($request->input('nilai', []) as $pesertaId => $nilaiInput) {
                    $peserta = PesertaUjian::where('ujian_id', $ujian->id)
                        ->where('id', $pesertaId)
                        ->firstOrFail();

                    $statusKehadiran = $request->input("status_kehadiran.{$pesertaId}", 'hadir');
                    if (!in_array($statusKehadiran, ['hadir', 'tidak_hadir'])) {
                        throw new \Exception('Status kehadiran hanya boleh hadir atau tidak_hadir.');
                    }

                    if ($statusKehadiran === 'tidak_hadir') {
                        $peserta->update([
                            'status_kehadiran' => 'tidak_hadir',
                            'nilai' => 0,
                            'keterangan' => 'tidak_hadir',
                        ]);
                        continue;
                    }

                    if (!is_numeric($nilaiInput) || $nilaiInput < 0 || $nilaiInput > 100) {
                        throw new \Exception("Nilai peserta {$peserta->id} harus angka 0 sampai 100.");
                    }

                    $nilai = round((float) $nilaiInput, 2);
                    $peserta->update([
                        'status_kehadiran' => 'hadir',
                        'nilai' => $nilai,
                        'keterangan' => $nilai >= (float) $ujian->kktp_value ? 'tercapai' : 'perlu_peningkatan',
                    ]);
                }

                $ujian->update(['status' => 'selesai']);
            });

            LogAktivitas::catat("Input manual Daftar Nilai T4 ujian: {$ujian->nama_ujian}", 'Daftar Nilai');

            return redirect()->route('daftar-nilai.index', $ujian)
                ->with('success', 'Daftar Nilai T4 berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}

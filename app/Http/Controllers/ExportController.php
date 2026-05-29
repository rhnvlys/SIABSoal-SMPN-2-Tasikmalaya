<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\AnalisisButir;
use App\Models\PesertaUjian;
use App\Models\PengaturanSekolah;
use App\Services\ReportService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    protected $reportService;
    protected $exportService;

    public function __construct(ReportService $reportService, ExportService $exportService)
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    // ===========================
    // DATA MENTAH T1
    // ===========================
    public function dataMentahExcel(Ujian $ujian)
    {
        $ujian->load(['soal', 'pesertaUjian.siswa', 'pesertaUjian.jawabanSiswa', 'guru', 'mapel']);

        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->with(['siswa', 'jawabanSiswa'])
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->get();

        $soalList = $ujian->soal;

        // Build CSV
        $header = ['No', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Kehadiran'];
        foreach ($soalList as $soal) {
            $header[] = 'Soal ' . $soal->nomor_soal;
        }
        $header = array_merge($header, ['Benar', 'Salah', 'Nilai', 'Keterangan']);

        $rows = [];
        foreach ($peserta as $idx => $p) {
            $keterangan = match($p->keterangan) {
                'tercapai' => 'TERCAPAI',
                'perlu_peningkatan' => 'PERLU PENINGKATAN',
                'tidak_hadir' => 'TIDAK HADIR',
                default => '-',
            };

            $row = [
                $idx + 1,
                $p->siswa->nis ?? '',
                $p->siswa->nisn ?? '',
                $p->siswa->nama_siswa ?? '',
                $p->siswa->jenis_kelamin ?? '',
                ucfirst(str_replace('_', ' ', $p->status_kehadiran)),
            ];

            $jawabanMap = $p->jawabanSiswa->keyBy('soal_id');
            foreach ($soalList as $soal) {
                $js = $jawabanMap->get($soal->id);
                $row[] = $js ? $js->skor_biner : 0;
            }

            $row[] = $p->jumlah_benar;
            $row[] = $p->jumlah_salah;
            $row[] = $p->nilai;
            $row[] = $keterangan;
            $rows[] = $row;
        }

        return $this->downloadCsv($header, $rows, "data_mentah_{$ujian->nama_ujian}");
    }

    public function dataMentahPdf(Ujian $ujian)
    {
        $ujian->load(['soal', 'guru', 'mapel', 'tahunAjaran', 'kelas']);

        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->with(['siswa', 'jawabanSiswa'])
            ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
            ->orderBy('siswa.nama_siswa')
            ->select('peserta_ujian.*')
            ->get();

        $sekolah = PengaturanSekolah::getSettings();

        $pdf = Pdf::loadView('exports.data_mentah_pdf', compact('ujian', 'peserta', 'sekolah'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download("data_mentah_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // OLAH DATA T2
    // ===========================
    public function olahDataExcel(Ujian $ujian)
    {
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('status_kehadiran', 'hadir')
            ->with('siswa')
            ->orderBy('ranking')
            ->get();

        $header = ['Ranking', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Benar', 'Salah', 'Nilai', 'Kelompok'];
        $rows = [];
        foreach ($peserta as $p) {
            $rows[] = [
                $p->ranking,
                $p->siswa->nis ?? '',
                $p->siswa->nisn ?? '',
                $p->siswa->nama_siswa ?? '',
                $p->siswa->jenis_kelamin ?? '',
                $p->jumlah_benar,
                $p->jumlah_salah,
                $p->nilai,
                strtoupper($p->kelompok ?? ''),
            ];
        }

        return $this->downloadCsv($header, $rows, "olah_data_{$ujian->nama_ujian}");
    }

    public function olahDataPdf(Ujian $ujian)
    {
        $ujian->load(['guru', 'mapel', 'tahunAjaran', 'kelas']);

        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('status_kehadiran', 'hadir')
            ->with('siswa')
            ->orderBy('ranking')
            ->get();

        $sekolah = PengaturanSekolah::getSettings();

        $pdf = Pdf::loadView('exports.olah_data_pdf', compact('ujian', 'peserta', 'sekolah'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("olah_data_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // ANALISIS T3 — Hanya DP & TK
    // ===========================
    public function analisisExcel(Ujian $ujian)
    {
        $analisis = AnalisisButir::where('ujian_id', $ujian->id)->orderBy('nomor_soal')->get();

        $header = [
            'No Soal',
            'BA',
            'BB',
            'JA',
            'JB',
            'N',
            'DP',
            'Kategori DP',
            'TK',
            'Kategori TK',
            'Keputusan',
        ];
        $rows = [];
        foreach ($analisis as $a) {
            $rows[] = [
                $a->nomor_soal,
                $a->ba,
                $a->bb,
                $a->ja,
                $a->jb,
                $a->n_analisis,
                $a->dp,
                $a->kategori_dp,
                $a->tk,
                $a->kategori_tk,
                $a->keputusan,
            ];
        }

        return $this->downloadCsv($header, $rows, "analisis_butir_{$ujian->nama_ujian}");
    }

    public function analisisPdf(Ujian $ujian)
    {
        $ujian->load(['guru', 'mapel', 'tahunAjaran', 'kelas']);
        $analisis = AnalisisButir::where('ujian_id', $ujian->id)->orderBy('nomor_soal')->get();
        $sekolah = PengaturanSekolah::getSettings();
        $ringkasan = $this->reportService->getRingkasanAnalisis($ujian->id);

        $pdf = Pdf::loadView('exports.analisis_pdf', compact('ujian', 'analisis', 'sekolah', 'ringkasan'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download("analisis_butir_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // DAFTAR NILAI T4
    // ===========================
    public function daftarNilaiExcel(Ujian $ujian)
    {
        $data = $this->reportService->getDaftarNilai($ujian->id);
        $peserta = $data['peserta'];

        $header = ['No', 'NIS', 'NISN', 'Nama Peserta Didik', 'L/P', 'Kehadiran', 'Skor PG', 'Salah', 'Nilai', 'Keterangan'];
        $rows = [];
        foreach ($peserta as $idx => $p) {
            $keterangan = match($p->keterangan) {
                'tercapai' => 'TERCAPAI',
                'perlu_peningkatan' => 'PERLU PENINGKATAN',
                'tidak_hadir' => 'TIDAK HADIR',
                default => '-',
            };

            $rows[] = [
                $idx + 1,
                $p->siswa->nis ?? '',
                $p->siswa->nisn ?? '',
                $p->siswa->nama_siswa ?? '',
                $p->siswa->jenis_kelamin ?? '',
                ucfirst(str_replace('_', ' ', $p->status_kehadiran)),
                $p->jumlah_benar,
                $p->jumlah_salah,
                $p->nilai,
                $keterangan,
            ];
        }

        return $this->downloadCsv($header, $rows, "daftar_nilai_{$ujian->nama_ujian}");
    }

    public function daftarNilaiPdf(Ujian $ujian)
    {
        $data = $this->reportService->getDaftarNilai($ujian->id);

        $pdf = Pdf::loadView('exports.daftar_nilai_pdf', $data)
                  ->setPaper('a4', 'portrait');

        return $pdf->download("daftar_nilai_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // REKAP NILAI T5
    // ===========================
    public function rekapNilaiExcel(Ujian $ujian)
    {
        $data = $this->reportService->getRekapNilai($ujian->id);
        $peserta = $data['peserta'];

        $header = ['No', 'NIS', 'NISN', 'Nama Peserta Didik', 'L/P', 'Kehadiran', 'Benar', 'Salah', 'Nilai', 'Keterangan'];
        $rows = [];
        foreach ($peserta as $idx => $p) {
            $keterangan = match($p->keterangan) {
                'tercapai' => 'TERCAPAI',
                'perlu_peningkatan' => 'PERLU PENINGKATAN',
                'tidak_hadir' => 'TIDAK HADIR',
                default => '-',
            };

            $rows[] = [
                $idx + 1,
                $p->siswa->nis ?? '',
                $p->siswa->nisn ?? '',
                $p->siswa->nama_siswa ?? '',
                $p->siswa->jenis_kelamin ?? '',
                ucfirst(str_replace('_', ' ', $p->status_kehadiran)),
                $p->jumlah_benar,
                $p->jumlah_salah,
                $p->nilai,
                $keterangan,
            ];
        }

        // Append summary
        $r = $data['ringkasan'];
        $rentang = $data['rentang_nilai'];
        $ketuntasan = $data['ketuntasan'];
        $rows[] = [];
        $rows[] = ['', '', '', 'RINGKASAN', '', '', '', '', '', ''];
        $rows[] = ['', '', '', 'Jumlah Siswa', $r['jumlah_siswa']];
        $rows[] = ['', '', '', 'Hadir', $r['hadir']];
        $rows[] = ['', '', '', 'Tidak Hadir', $r['tidak_hadir']];
        $rows[] = ['', '', '', 'Nilai Tertinggi', $r['nilai_tertinggi']];
        $rows[] = ['', '', '', 'Nilai Terendah', $r['nilai_terendah']];
        $rows[] = ['', '', '', 'Rata-rata', $r['rata_rata']];
        $rows[] = ['', '', '', 'Nilai < KKM', $rentang['bawah_kkm']];
        $rows[] = ['', '', '', 'Nilai = KKM', $rentang['sama_kkm']];
        $rows[] = ['', '', '', 'Nilai > KKM', $rentang['atas_kkm']];
        $rows[] = ['', '', '', 'Tuntas', $ketuntasan['tuntas']];
        $rows[] = ['', '', '', 'Belum Tuntas', $ketuntasan['belum_tuntas']];

        return $this->downloadCsv($header, $rows, "rekap_nilai_{$ujian->nama_ujian}");
    }

    public function rekapNilaiPdf(Ujian $ujian)
    {
        $data = $this->reportService->getRekapNilai($ujian->id);

        $pdf = Pdf::loadView('exports.rekap_nilai_pdf', $data)
                  ->setPaper('a4', 'portrait');

        return $pdf->download("rekap_nilai_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // HELPER: CSV Download
    // ===========================
    private function downloadCsv(array $header, array $rows, string $filename)
    {
        return $this->exportService->downloadExcel($header, $rows, $filename);
    }
}

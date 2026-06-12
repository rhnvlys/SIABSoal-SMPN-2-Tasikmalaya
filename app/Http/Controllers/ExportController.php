<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\AnalisisButir;
use App\Models\LogAktivitas;
use App\Models\PesertaUjian;
use App\Models\PengaturanSekolah;
use App\Services\ReportService;
use App\Services\ExportService;
use App\Exports\AssessmentTemplateExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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
    public function dataMentahExcel(Ujian $ujian, ?string $filename = null)
    {
        $this->catatExport($ujian, 'Data Mentah T1', 'Excel');
        $filename = "data_mentah_{$ujian->nama_ujian}.xlsx";
        return Excel::download(new AssessmentTemplateExport('skor-01', $ujian), $filename);
    }

    public function dataMentahPdf(Ujian $ujian, ?string $filename = null)
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

        $this->catatExport($ujian, 'Data Mentah T1', 'PDF');

        return $pdf->download("data_mentah_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // OLAH DATA T2
    // ===========================
    public function olahDataExcel(Ujian $ujian, ?string $filename = null)
    {
        $this->catatExport($ujian, 'Olah Data T2', 'Excel');
        $filename = "olah_data_{$ujian->nama_ujian}.xlsx";
        return Excel::download(new AssessmentTemplateExport('skor-01', $ujian), $filename);
    }

    public function olahDataPdf(Ujian $ujian, ?string $filename = null)
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

        $this->catatExport($ujian, 'Olah Data T2', 'PDF');

        return $pdf->download("olah_data_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // ANALISIS T3 — Hanya DP & TK
    // ===========================
    public function analisisExcel(Ujian $ujian, ?string $filename = null)
    {
        $this->catatExport($ujian, 'Analisis Data T3', 'Excel');
        $filename = "analisis_butir_{$ujian->nama_ujian}.xlsx";
        return Excel::download(new AssessmentTemplateExport('skor-01', $ujian), $filename);
    }

    public function analisisPdf(Ujian $ujian, ?string $filename = null)
    {
        $ujian->load(['guru', 'mapel', 'tahunAjaran', 'kelas']);
        $analisis = AnalisisButir::where('ujian_id', $ujian->id)->orderBy('nomor_soal')->get();
        $sekolah = PengaturanSekolah::getSettings();
        $ringkasan = $this->reportService->getRingkasanAnalisis($ujian->id);

        $pdf = Pdf::loadView('exports.analisis_pdf', compact('ujian', 'analisis', 'sekolah', 'ringkasan'))
                  ->setPaper('a4', 'landscape');

        $this->catatExport($ujian, 'Analisis Data T3', 'PDF');

        return $pdf->download("analisis_butir_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // DAFTAR NILAI T4
    // ===========================
    public function daftarNilaiExcel(Ujian $ujian, ?string $filename = null)
    {
        $this->catatExport($ujian, 'Daftar Nilai T4', 'Excel');
        $filename = "daftar_nilai_{$ujian->nama_ujian}.xlsx";
        return Excel::download(new AssessmentTemplateExport('skor-01', $ujian), $filename);
    }

    public function daftarNilaiPdf(Ujian $ujian, ?string $filename = null)
    {
        $data = $this->reportService->getDaftarNilai($ujian->id);

        $pdf = Pdf::loadView('exports.daftar_nilai_pdf', $data)
                  ->setPaper('a4', 'portrait');

        $this->catatExport($data['ujian'], 'Daftar Nilai T4', 'PDF');

        return $pdf->download("daftar_nilai_{$ujian->nama_ujian}.pdf");
    }

    // ===========================
    // REKAP NILAI T5
    // ===========================
    public function rekapNilaiExcel(Ujian $ujian, ?string $filename = null)
    {
        $this->catatExport($ujian, 'Rekap Nilai T5', 'Excel');
        $filename = "rekap_nilai_{$ujian->nama_ujian}.xlsx";
        return Excel::download(new AssessmentTemplateExport('skor-01', $ujian), $filename);
    }

    public function rekapNilaiPdf(Ujian $ujian, ?string $filename = null)
    {
        $data = $this->reportService->getRekapNilai($ujian->id);

        $pdf = Pdf::loadView('exports.rekap_nilai_pdf', $data)
                  ->setPaper('a4', 'portrait');

        $this->catatExport($data['ujian'], 'Rekap Nilai T5', 'PDF');

        return $pdf->download("rekap_nilai_{$ujian->nama_ujian}.pdf");
    }

    private function downloadReport(Ujian $ujian, array $header, array $rows, string $filename, string $title, string $namaLaporan)
    {
        $this->catatExport($ujian, $namaLaporan, 'Excel');

        return $this->exportService->downloadExcel($header, $rows, $filename, $title, $this->reportMetaRows($ujian, $namaLaporan));
    }

    private function reportMetaRows(Ujian $ujian, string $namaLaporan): array
    {
        $ujian->loadMissing(['guru', 'mapel', 'tahunAjaran', 'kelas']);

        return [
            ['SMP NEGERI 2 TASIKMALAYA'],
            ['SIABSoal SMPN 2 Tasikmalaya'],
            ['Nama Laporan', $namaLaporan],
            ['Nama Ujian', $ujian->nama_ujian],
            ['Nama Guru', $ujian->guru->nama_guru ?? '-'],
            ['Mata Pelajaran', $ujian->mapel->nama_mapel ?? '-'],
            ['Kelas', $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-'],
            ['Semester', $ujian->tahunAjaran->semester ?? '-'],
            ['Tahun Ajaran', $ujian->tahunAjaran->tahun_ajaran ?? '-'],
            ['Jenis Penilaian', $ujian->jenis_penilaian_label],
            ['Tujuan Pembelajaran', $ujian->tujuan_pembelajaran ?: '-'],
            ['Lingkup Materi', $ujian->lingkup_materi ?: '-'],
            ['KKTP/KKM', $ujian->kktp_value],
            ['Tanggal Cetak', now()->format('d/m/Y H:i')],
        ];
    }

    private function catatExport(Ujian $ujian, string $namaLaporan, string $format): void
    {
        LogAktivitas::catat(
            "Export {$format}",
            'Laporan Export',
            "Mengekspor {$namaLaporan} {$format} untuk ujian: {$ujian->nama_ujian}"
        );
    }
}

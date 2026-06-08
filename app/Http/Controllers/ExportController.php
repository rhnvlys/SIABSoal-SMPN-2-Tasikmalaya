<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\AnalisisButir;
use App\Models\LogAktivitas;
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

        $header = ['No', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Status Kehadiran'];
        foreach ($soalList as $soal) {
            $header[] = 'Soal ' . $soal->nomor_soal;
        }
        $header = array_merge($header, ['Jumlah Benar', 'Jumlah Salah', 'Nilai', 'Keterangan']);

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

        return $this->downloadReport($ujian, $header, $rows, "data_mentah_{$ujian->nama_ujian}", 'DATA_MENTAH_T1', 'DATA MENTAH T1');
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

        $this->catatExport($ujian, 'Data Mentah T1', 'PDF');

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

        return $this->downloadReport($ujian, $header, $rows, "olah_data_{$ujian->nama_ujian}", 'OLAH_DATA_T2', 'OLAH DATA T2');
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

        $this->catatExport($ujian, 'Olah Data T2', 'PDF');

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
                $a->dp,
                $a->kategori_dp,
                $a->tk,
                $a->kategori_tk,
                $a->keputusan,
            ];
        }

        return $this->downloadReport($ujian, $header, $rows, "analisis_butir_{$ujian->nama_ujian}", 'ANALISIS_DATA_T3', 'ANALISIS DATA T3');
    }

    public function analisisPdf(Ujian $ujian)
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
    public function daftarNilaiExcel(Ujian $ujian)
    {
        $data = $this->reportService->getDaftarNilai($ujian->id);
        $peserta = $data['peserta'];

        $header = ['No', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Status Kehadiran', 'Jumlah Benar', 'Jumlah Salah', 'Nilai', 'Keterangan'];
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

        return $this->downloadReport($data['ujian'], $header, $rows, "daftar_nilai_{$data['ujian']->nama_ujian}", 'DAFTAR_NILAI_T4', 'DAFTAR NILAI T4');
    }

    public function daftarNilaiPdf(Ujian $ujian)
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
    public function rekapNilaiExcel(Ujian $ujian)
    {
        $data = $this->reportService->getRekapNilai($ujian->id);
        $r = $data['ringkasan'];
        $rentang = $data['rentang_nilai'];
        $ketuntasan = $data['ketuntasan'];
        $analisis = $data['analisis_ringkasan'];

        $header = ['Bagian', 'Komponen', 'Nilai'];

        $rows = [
            ['Identitas Ujian', 'Nama Ujian', $data['ujian']->nama_ujian],
            ['Identitas Ujian', 'Jenis Penilaian', $data['ujian']->jenis_penilaian_label],
            ['Identitas Ujian', 'Guru', $data['ujian']->guru->nama_guru ?? '-'],
            ['Identitas Ujian', 'Mata Pelajaran', $data['ujian']->mapel->nama_mapel ?? '-'],
            ['Identitas Ujian', 'Kelas', $data['ujian']->kelas->pluck('nama_kelas')->join(', ') ?: '-'],
            ['Identitas Ujian', 'KKTP/KKM', $data['ujian']->kktp_value],
            ['Rekap Kehadiran', 'Jumlah Siswa', $r['jumlah_siswa']],
            ['Rekap Kehadiran', 'Hadir', $r['hadir']],
            ['Rekap Kehadiran', 'Tidak Hadir', $r['tidak_hadir']],
            ['Statistik Nilai', 'Nilai Tertinggi', $r['nilai_tertinggi']],
            ['Statistik Nilai', 'Nilai Terendah', $r['nilai_terendah']],
            ['Statistik Nilai', 'Rata-rata', $r['rata_rata']],
            ['Rentang KKTP/KKM', 'Jumlah nilai di bawah KKTP', $rentang['bawah_kkm']],
            ['Rentang KKTP/KKM', 'Jumlah nilai sama dengan KKTP', $rentang['sama_kkm']],
            ['Rentang KKTP/KKM', 'Jumlah nilai di atas KKTP', $rentang['atas_kkm']],
            ['Ketuntasan', 'Jumlah Tuntas', $ketuntasan['tuntas']],
            ['Ketuntasan', 'Jumlah Belum Tuntas', $ketuntasan['belum_tuntas']],
            ['Ringkasan DP', 'Soal Baik', $analisis['soal_baik']],
            ['Ringkasan DP', 'Soal Revisi', $analisis['soal_revisi']],
            ['Ringkasan DP', 'Soal Buang', $analisis['soal_buang']],
            ['Ringkasan TK', 'Soal Mudah', $analisis['soal_mudah']],
            ['Ringkasan TK', 'Soal Sedang', $analisis['soal_sedang']],
            ['Ringkasan TK', 'Soal Sukar', $analisis['soal_sukar']],
            ['Kesimpulan', 'Kesimpulan Singkat', $data['kesimpulan']],
        ];

        return $this->downloadReport($data['ujian'], $header, $rows, "rekap_nilai_{$data['ujian']->nama_ujian}", 'REKAP_NILAI_T5', 'REKAP NILAI T5');
    }

    public function rekapNilaiPdf(Ujian $ujian)
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

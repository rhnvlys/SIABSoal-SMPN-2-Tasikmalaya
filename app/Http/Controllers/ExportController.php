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
    public function dataMentahExcel(Ujian $ujian, ?string $filename = null)
    {
        $ujian->load(['soal' => fn ($query) => $query->orderBy('nomor_soal')]);
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->with(['siswa:id,nis,nisn,nama_siswa,jenis_kelamin', 'jawabanSiswa:id,peserta_ujian_id,soal_id,skor_biner'])
            ->orderBy('id')
            ->get();
        $header = ['No', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Kehadiran'];
        foreach ($ujian->soal as $soal) {
            $header[] = 'Soal ' . $soal->nomor_soal;
        }
        $header = array_merge($header, ['Benar', 'Salah', 'Nilai', 'Keterangan']);
        $rows = $peserta->values()->map(function (PesertaUjian $peserta, int $index) use ($ujian): array {
            $answers = $peserta->jawabanSiswa->keyBy('soal_id');
            $row = [
                $index + 1,
                $peserta->siswa->nis ?? '-',
                $peserta->siswa->nisn ?? '-',
                $peserta->siswa->nama_siswa ?? '-',
                $peserta->siswa->jenis_kelamin ?? '-',
                $peserta->status_kehadiran === 'hadir' ? 'HADIR' : 'TIDAK HADIR',
            ];
            foreach ($ujian->soal as $soal) {
                $row[] = $peserta->status_kehadiran === 'hadir'
                    ? (int) ($answers->get($soal->id)?->skor_biner ?? 0)
                    : 0;
            }

            return array_merge($row, [
                $peserta->jumlah_benar,
                $peserta->jumlah_salah,
                (float) $peserta->nilai,
                $this->keteranganLabel($peserta->keterangan),
            ]);
        })->all();

        return $this->downloadReport(
            $ujian,
            $header,
            $rows,
            "data_mentah_{$ujian->nama_ujian}",
            'DATA MENTAH T1',
            'Data Mentah T1'
        );
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
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->with('siswa:id,nis,nisn,nama_siswa,jenis_kelamin')
            ->orderByRaw('ranking IS NULL')
            ->orderBy('ranking')
            ->get();
        $rows = $peserta->map(fn (PesertaUjian $p) => [
            $p->ranking ?? '-',
            $p->siswa->nis ?? '-',
            $p->siswa->nisn ?? '-',
            $p->siswa->nama_siswa ?? '-',
            $p->siswa->jenis_kelamin ?? '-',
            $p->status_kehadiran === 'hadir' ? 'HADIR' : 'TIDAK HADIR',
            $p->jumlah_benar,
            $p->jumlah_salah,
            (float) $p->nilai,
            $p->kelompok ? strtoupper($p->kelompok) : '-',
        ])->all();

        return $this->downloadReport(
            $ujian,
            ['Ranking', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Kehadiran', 'Benar', 'Salah', 'Nilai', 'Kelompok'],
            $rows,
            "olah_data_{$ujian->nama_ujian}",
            'OLAH DATA T2',
            'Olah Data T2'
        );
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
        $rows = AnalisisButir::where('ujian_id', $ujian->id)
            ->orderBy('nomor_soal')
            ->get(['nomor_soal', 'ba', 'bb', 'ja', 'jb', 'dp', 'kategori_dp', 'tk', 'kategori_tk', 'keputusan'])
            ->map(fn (AnalisisButir $a) => [
                $a->nomor_soal, $a->ba, $a->bb, $a->ja, $a->jb,
                (float) $a->dp, $a->kategori_dp, (float) $a->tk, $a->kategori_tk, $a->keputusan,
            ])->all();

        return $this->downloadReport(
            $ujian,
            ['No Soal', 'BA', 'BB', 'JA', 'JB', 'DP', 'Kategori DP', 'TK', 'Kategori TK', 'Keputusan'],
            $rows,
            "analisis_butir_{$ujian->nama_ujian}",
            'ANALISIS DATA T3',
            'Analisis Data T3'
        );
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
        $data = $this->reportService->getDaftarNilai($ujian->id);
        $rows = $data['peserta']->values()->map(fn (PesertaUjian $p, int $index) => [
            $index + 1,
            $p->siswa->nis ?? '-',
            $p->siswa->nisn ?? '-',
            $p->siswa->nama_siswa ?? '-',
            $p->siswa->jenis_kelamin ?? '-',
            $p->status_kehadiran === 'hadir' ? 'HADIR' : 'TIDAK HADIR',
            $p->jumlah_benar,
            $p->jumlah_salah,
            (float) $p->nilai,
            $this->keteranganLabel($p->keterangan),
        ])->all();

        return $this->downloadReport(
            $ujian,
            ['No', 'NIS', 'NISN', 'Nama Siswa', 'L/P', 'Kehadiran', 'Benar', 'Salah', 'Nilai', 'Keterangan'],
            $rows,
            "daftar_nilai_{$ujian->nama_ujian}",
            'DAFTAR NILAI T4',
            'Daftar Nilai T4'
        );
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
        $data = $this->reportService->getRekapNilai($ujian->id);
        $r = $data['ringkasan'];
        $k = $data['ketuntasan'];
        $a = $data['analisis_ringkasan'];
        $rows = [
            ['Jumlah Siswa', $r['jumlah_siswa']],
            ['Hadir', $r['hadir']],
            ['Tidak Hadir', $r['tidak_hadir']],
            ['Nilai Tertinggi', $r['nilai_tertinggi']],
            ['Nilai Terendah', $r['nilai_terendah']],
            ['Rata-rata', $r['rata_rata']],
            ['Tuntas', $k['tuntas']],
            ['Belum Tuntas', $k['belum_tuntas']],
            ['Soal Baik', $a['soal_baik']],
            ['Soal Revisi', $a['soal_revisi']],
            ['Soal Buang', $a['soal_buang']],
            ['Kesimpulan', $data['kesimpulan']],
        ];

        return $this->downloadReport(
            $ujian,
            ['Indikator', 'Hasil'],
            $rows,
            "rekap_nilai_{$ujian->nama_ujian}",
            'REKAP NILAI T5',
            'Rekap Nilai T5'
        );
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

    private function keteranganLabel(?string $keterangan): string
    {
        return match ($keterangan) {
            'tercapai' => 'TERCAPAI',
            'perlu_peningkatan' => 'PERLU PENINGKATAN',
            'tidak_hadir' => 'TIDAK HADIR',
            default => '-',
        };
    }
}

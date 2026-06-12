<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RekapNilaiSheet extends BaseSheet
{
    private int $pesertaCount;
    private int $jumlahSoal;

    public function __construct(
        private readonly ?Ujian $ujian,
        private readonly string $sheetTitle = 'REKAP_NILAI',
        private readonly string $daftarNilaiSheetTitle = 'DAFTAR_NILAI'
    )
    {
        $this->title = $sheetTitle;
        
        $pesertaList = $this->ujian
            ? $this->ujian->pesertaUjian()->get()
            : collect();
        $this->pesertaCount = max($pesertaList->count(), 5);
        $this->jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_REKAP);
                $this->setSheetFooter($sheet, 'REKAP NILAI T5');

                // Heading
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', '📋  REKAPITULASI ADMINISTRASI PENILAIAN GURU');
                $this->applyHeaderStyle($sheet, 'A1:G1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // Subheading
                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'SMP NEGERI 2 TASIKMALAYA - SIABSoal');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true, 'color' => ['rgb' => '1B365D']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Dynamic references
                $pRow = 12 + $this->pesertaCount;
                $jsRow = 9 + $this->jumlahSoal;

                $daftarSheet = "'" . str_replace("'", "''", $this->daftarNilaiSheetTitle) . "'";
                $refJmlSiswa = "={$daftarSheet}!F" . $pRow;
                $refHadir = "={$daftarSheet}!F" . ($pRow + 1);
                $refTidakHadir = "={$daftarSheet}!F" . ($pRow + 2);
                $refMax = "={$daftarSheet}!F" . ($pRow + 3);
                $refMin = "={$daftarSheet}!F" . ($pRow + 4);
                $refAvg = "={$daftarSheet}!F" . ($pRow + 5);
                $refTuntas = "={$daftarSheet}!F" . ($pRow + 6);
                $refRemedial = "={$daftarSheet}!F" . ($pRow + 7);

                $refSoalBaik = "=ANALISIS_T3!E" . $jsRow;
                $refSoalRevisi = "=ANALISIS_T3!E" . ($jsRow + 1);
                $refSoalBuang = "=ANALISIS_T3!E" . ($jsRow + 2);
                $refSoalMudah = "=ANALISIS_T3!E" . ($jsRow + 3);
                $refSoalSedang = "=ANALISIS_T3!E" . ($jsRow + 4);
                $refSoalSukar = "=ANALISIS_T3!E" . ($jsRow + 5);

                // BLOCK 1: IDENTITAS UJIAN (Left side)
                $sheet->mergeCells('A4:C4');
                $sheet->setCellValue('A4', 'IDENTITAS UJIAN');
                $this->applySubHeaderStyle($sheet, 'A4:C4');
                $sheet->getRowDimension(4)->setRowHeight(22);

                $identitas = [
                    'Nama Ujian' => $this->ujian?->nama_ujian ?? '-',
                    'Jenis Penilaian' => $this->ujian?->jenis_penilaian_label ?? '-',
                    'Guru Pengampu' => $this->ujian?->guru?->nama_guru ?? '-',
                    'Mata Pelajaran' => $this->ujian?->mapel?->nama_mapel ?? '-',
                    'Kelas' => $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-',
                    'KKTP/KKM' => $this->ujian?->kktp_value ?? 75,
                ];

                $r = 5;
                foreach ($identitas as $label => $value) {
                    $sheet->setCellValue("A{$r}", $label);
                    $sheet->setCellValue("B{$r}", $value);
                    $sheet->mergeCells("B{$r}:C{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                    $this->applyBorder($sheet, "A{$r}:C{$r}");
                    $sheet->getRowDimension($r)->setRowHeight(20);
                    $r++;
                }

                // BLOCK 2: REKAP KEHADIRAN (Left side)
                $r += 1;
                $sheet->mergeCells("A{$r}:C{$r}");
                $sheet->setCellValue("A{$r}", 'REKAP KEHADIRAN');
                $this->applySubHeaderStyle($sheet, "A{$r}:C{$r}");
                $sheet->getRowDimension($r)->setRowHeight(22);
                $r++;

                $kehadiran = [
                    'Jumlah Siswa' => $refJmlSiswa,
                    'Hadir' => $refHadir,
                    'Tidak Hadir' => $refTidakHadir,
                ];

                foreach ($kehadiran as $label => $formula) {
                    $sheet->setCellValue("A{$r}", $label);
                    $sheet->setCellValue("B{$r}", $formula);
                    $sheet->mergeCells("B{$r}:C{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $this->applyBorder($sheet, "A{$r}:C{$r}");
                    $sheet->getRowDimension($r)->setRowHeight(20);
                    $r++;
                }

                // BLOCK 3: STATISTIK NILAI (Right side)
                $sheet->mergeCells('E4:G4');
                $sheet->setCellValue('E4', 'STATISTIK PENILAIAN');
                $this->applySubHeaderStyle($sheet, 'E4:G4');

                $statistik = [
                    'Nilai Tertinggi' => $refMax,
                    'Nilai Terendah' => $refMin,
                    'Rata-rata Kelas' => $refAvg,
                    'Siswa Tuntas (>= KKTP)' => $refTuntas,
                    'Belum Tuntas (< KKTP)' => $refRemedial,
                ];

                $r2 = 5;
                foreach ($statistik as $label => $formula) {
                    $sheet->setCellValue("E{$r2}", $label);
                    $sheet->setCellValue("F{$r2}", $formula);
                    $sheet->mergeCells("F{$r2}:G{$r2}");
                    $sheet->getStyle("E{$r2}")->getFont()->setBold(true);
                    $sheet->getStyle("F{$r2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $this->applyBorder($sheet, "E{$r2}:G{$r2}");
                    $sheet->getRowDimension($r2)->setRowHeight(20);
                    $r2++;
                }

                // BLOCK 4: REKAP KUALITAS SOAL (Right side)
                $r2 += 2;
                $sheet->mergeCells("E{$r2}:G{$r2}");
                $sheet->setCellValue("E{$r2}", 'REKAP KUALITAS SOAL');
                $this->applySubHeaderStyle($sheet, "E{$r2}:G{$r2}");
                $r2++;

                $kualitas = [
                    'Soal Baik / Diterima' => $refSoalBaik,
                    'Soal Perlu Revisi' => $refSoalRevisi,
                    'Soal Dibuang' => $refSoalBuang,
                    'Kategori Mudah' => $refSoalMudah,
                    'Kategori Sedang' => $refSoalSedang,
                    'Kategori Sukar' => $refSoalSukar,
                ];

                foreach ($kualitas as $label => $formula) {
                    $sheet->setCellValue("E{$r2}", $label);
                    $sheet->setCellValue("F{$r2}", $formula);
                    $sheet->mergeCells("F{$r2}:G{$r2}");
                    $sheet->getStyle("E{$r2}")->getFont()->setBold(true);
                    $sheet->getStyle("F{$r2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $this->applyBorder($sheet, "E{$r2}:G{$r2}");
                    $sheet->getRowDimension($r2)->setRowHeight(20);
                    $r2++;
                }

                // Find max row to write Kesimpulan
                $maxRow = max($r, $r2) + 2;

                // BLOCK 5: KESIMPULAN (Merged Bottom block)
                $sheet->mergeCells("A{$maxRow}:G{$maxRow}");
                $sheet->setCellValue("A{$maxRow}", 'KESIMPULAN EVALUASI GURU');
                $this->applySubHeaderStyle($sheet, "A{$maxRow}:G{$maxRow}");
                $sheet->getRowDimension($maxRow)->setRowHeight(22);
                $maxRow++;

                $sheet->mergeCells("A{$maxRow}:G" . ($maxRow + 3));
                
                // Formula to build dynamic text conclusion!
                $conclusionFormula = "=\"Berdasarkan hasil penilaian pada mata pelajaran \" & B8 & \" kelas \" & B9 & \", dari total \" & TEXT(B12,\"0\") & \" siswa, sebanyak \" & TEXT(B13,\"0\") & \" siswa mengikuti ujian dan \" & TEXT(B14,\"0\") & \" siswa berhalangan hadir. Rata-rata nilai kelas diperoleh \" & TEXT(F7,\"0.00\") & \" dengan nilai tertinggi \" & TEXT(F5,\"0\") & \" dan terendah \" & TEXT(F6,\"0\") & \". Sebanyak \" & TEXT(F8,\"0\") & \" siswa telah mencapai kriteria ketuntasan (KKTP), sedangkan \" & TEXT(F9,\"0\") & \" siswa memerlukan bimbingan remedial.\"";
                
                $sheet->setCellValue("A{$maxRow}", $conclusionFormula);
                
                $sheet->getStyle("A{$maxRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ]
                ]);
                $this->applyBorder($sheet, "A{$maxRow}:G" . ($maxRow + 3));

                for ($i = 0; $i < 4; $i++) {
                    $sheet->getRowDimension($maxRow + $i)->setRowHeight(18);
                }

                // Column dimensions
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(8);
                $sheet->getColumnDimension('D')->setWidth(4);
                $sheet->getColumnDimension('E')->setWidth(25);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(8);
            }
        ];
    }
}

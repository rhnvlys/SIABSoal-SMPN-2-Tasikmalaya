<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AnalisisT3Sheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'ANALISIS_T3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_ANALISIS);
                $this->setSheetFooter($sheet, 'ANALISIS BUTIR SOAL T3');

                // ── Heading ───────────────────────────────────────────
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', '📈  ANALISIS BUTIR SOAL (TINGKAT KESUKARAN & DAYA PEMBEDA)');
                $this->applyHeaderStyle($sheet, 'A1:K1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // ── Ujian Details ─────────────────────────────────────
                $sheet->setCellValue('A3', 'Mata Pelajaran:');
                $sheet->setCellValue('B3', $this->ujian?->mapel?->nama_mapel ?? '-');
                $sheet->setCellValue('G3', 'Kelas:');
                $sheet->setCellValue('H3', $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-');

                $sheet->setCellValue('A4', 'Nama Ujian:');
                $sheet->setCellValue('B4', $this->ujian?->nama_ujian ?? '-');
                $sheet->setCellValue('G4', 'KKTP/KKM:');
                $sheet->setCellValue('H4', $this->ujian?->kktp_value ?? 75);

                $this->applyMetaLabel($sheet, 'A3');
                $this->applyMetaLabel($sheet, 'A4');
                $this->applyMetaLabel($sheet, 'G3');
                $this->applyMetaLabel($sheet, 'G4');

                // ── Table Headers ─────────────────────────────────────
                $headers = ['No Soal', 'Kode TP', 'BA', 'BB', 'JA', 'JB', 'DP', 'Kategori DP', 'TK', 'Kategori TK', 'Keputusan'];
                foreach ($headers as $colIdx => $header) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '6';
                    $sheet->setCellValue($cell, $header);
                }
                $this->applySubHeaderStyle($sheet, 'A6:K6');
                $sheet->getRowDimension(6)->setRowHeight(28);

                // ── Data Rows ─────────────────────────────────────────
                $analisisList = $this->ujian ? $this->ujian->analisisButir->sortBy('nomor_soal') : collect();
                $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;

                $row = 7;
                $startRow = $row;
                for ($i = 1; $i <= $jumlahSoal; $i++) {
                    $a = $analisisList->where('nomor_soal', $i)->first();

                    $sheet->setCellValue("A{$row}", $i);
                    $sheet->setCellValue("B{$row}", $a?->soal?->kode_tp ?? 'TP 1');
                    $sheet->setCellValue("C{$row}", $a?->ba ?? 0);
                    $sheet->setCellValue("D{$row}", $a?->bb ?? 0);
                    $sheet->setCellValue("E{$row}", $a?->ja ?? 0);
                    $sheet->setCellValue("F{$row}", $a?->jb ?? 0);
                    $sheet->setCellValue("G{$row}", $a?->dp ?? 0.00);
                    $sheet->setCellValue("H{$row}", $a?->kategori_dp ?? 'Sangat Baik');
                    $sheet->setCellValue("I{$row}", $a?->tk ?? 0.00);
                    $sheet->setCellValue("J{$row}", $a?->kategori_tk ?? 'Sedang');
                    $sheet->setCellValue("K{$row}", $a?->keputusan ?? 'Diterima');

                    // ── Center all columns ────────────────────────────
                    for ($c = 1; $c <= 11; $c++) {
                        $sheet->getStyle(Coordinate::stringFromColumnIndex($c) . $row)
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // ── Number formatting for DP/TK ───────────────────
                    $this->applyPercentFormat($sheet, "G{$row}");
                    $this->applyPercentFormat($sheet, "I{$row}");

                    // ── Color-code Kategori DP ────────────────────────
                    $this->applyStatusBadge($sheet, "H{$row}", $a?->kategori_dp ?? 'Sangat Baik');

                    // ── Color-code Kategori TK ────────────────────────
                    $this->applyStatusBadge($sheet, "J{$row}", $a?->kategori_tk ?? 'Sedang');

                    // ── Color-code Keputusan ──────────────────────────
                    $this->applyStatusBadge($sheet, "K{$row}", $a?->keputusan ?? 'Diterima');

                    // ── Zebra striping for non-badge columns ──────────
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:G{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::CLR_ZEBRA_EVEN],
                        ]);
                        $sheet->getStyle("I{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::CLR_ZEBRA_EVEN],
                        ]);
                    }

                    $this->applyBorder($sheet, "A{$row}:K{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(24);
                    $row++;
                }

                $endRow = $row - 1;

                // ── Spacing ───────────────────────────────────────────
                $row += 2;

                // ── Summary Section ───────────────────────────────────
                $sheet->mergeCells("A{$row}:C" . ($row + 5));
                $sheet->setCellValue("A{$row}", "📊\nRINGKASAN\nHASIL\nBUTIR SOAL");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::CLR_NAVY]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_BLUE_HEADER]],
                ]);
                $this->applyThickBorder($sheet, "A{$row}:C" . ($row + 5));

                $summaries = [
                    ['✅ Soal Baik / Diterima', "=COUNTIF(K{$startRow}:K{$endRow}, \"*terima*\") + COUNTIF(K{$startRow}:K{$endRow}, \"*Baik*\") + COUNTIF(K{$startRow}:K{$endRow}, \"*pakai*\")", self::CLR_GREEN_BG],
                    ['⚡ Soal Perlu Revisi', "=COUNTIF(K{$startRow}:K{$endRow}, \"*revisi*\") + COUNTIF(K{$startRow}:K{$endRow}, \"*perbaiki*\")", self::CLR_YELLOW_BG],
                    ['❌ Soal Dibuang', "=COUNTIF(K{$startRow}:K{$endRow}, \"*buang*\")", self::CLR_RED_BG],
                    ['🟢 Total Soal Mudah', "=COUNTIF(J{$startRow}:J{$endRow}, \"*Mudah*\")", self::CLR_GREEN_BG],
                    ['🟡 Total Soal Sedang', "=COUNTIF(J{$startRow}:J{$endRow}, \"*Sedang*\")", self::CLR_YELLOW_BG],
                    ['🔴 Total Soal Sukar', "=COUNTIF(J{$startRow}:J{$endRow}, \"*Sukar*\")", self::CLR_RED_BG],
                ];

                $sumRow = $row;
                foreach ($summaries as [$label, $formula, $bgColor]) {
                    $sheet->setCellValue("D{$sumRow}", $label);
                    $sheet->setCellValue("E{$sumRow}", $formula);
                    $sheet->mergeCells("E{$sumRow}:K{$sumRow}");

                    $sheet->getStyle("D{$sumRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    ]);
                    $sheet->getStyle("E{$sumRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    $this->applyBorder($sheet, "D{$sumRow}:K{$sumRow}");
                    $sheet->getRowDimension($sumRow)->setRowHeight(24);
                    $sumRow++;
                }

                // ── Column Dimensions ─────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(10);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(8);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(8);
                $sheet->getColumnDimension('G')->setWidth(10);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(10);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(18);

                // ── Print Setup ───────────────────────────────────────
                $this->setPrintArea($sheet, "A1:K{$sumRow}");
            }
        ];
    }
}

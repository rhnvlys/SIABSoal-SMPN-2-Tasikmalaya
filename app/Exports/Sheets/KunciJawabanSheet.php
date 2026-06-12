<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class KunciJawabanSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'KUNCI_JAWABAN';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_IDENTITAS);
                $this->setSheetFooter($sheet, 'KUNCI JAWABAN');

                // Heading
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', '🔑  KUNCI JAWABAN UJIAN');
                $this->applyHeaderStyle($sheet, 'A1:E1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // Ujian Metadata
                $sheet->setCellValue('A3', 'Mata Pelajaran:');
                $sheet->setCellValue('B3', $this->ujian?->mapel?->nama_mapel ?? '-');
                $sheet->setCellValue('D3', 'Kelas:');
                $sheet->setCellValue('E3', $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-');

                $sheet->setCellValue('A4', 'Nama Ujian:');
                $sheet->setCellValue('B4', $this->ujian?->nama_ujian ?? '-');
                $sheet->setCellValue('D4', 'KKTP/KKM:');
                $sheet->setCellValue('E4', $this->ujian?->kktp_value ?? 75);

                $this->applyMetaLabel($sheet, 'A3');
                $this->applyMetaLabel($sheet, 'A4');
                $this->applyMetaLabel($sheet, 'D3');
                $this->applyMetaLabel($sheet, 'D4');

                // Table Headers
                $headers = ['nomor_soal', 'kode_tp', 'lingkup_materi', 'kunci_jawaban', 'bobot'];
                foreach ($headers as $colIdx => $header) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '6';
                    $sheet->setCellValue($cell, $header);
                }
                $this->applySubHeaderStyle($sheet, 'A6:E6');
                $sheet->getRowDimension(6)->setRowHeight(25);

                $soalList = $this->ujian ? $this->ujian->soal->sortBy('nomor_soal') : collect();
                $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;

                $row = 7;
                for ($i = 1; $i <= $jumlahSoal; $i++) {
                    $soal = $soalList->where('nomor_soal', $i)->first();
                    
                    $sheet->setCellValue("A{$row}", $i);
                    $sheet->setCellValue("B{$row}", $soal?->kode_tp ?? 'TP 1');
                    $sheet->setCellValue("C{$row}", $soal?->lingkup_materi ?? $this->ujian?->lingkup_materi ?? '');
                    $sheet->setCellValue("D{$row}", $soal?->kunci_jawaban ?? '');
                    $sheet->setCellValue("E{$row}", $soal?->bobot ?? 1);

                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $this->applyBorder($sheet, "A{$row}:E{$row}");
                    $this->applyInputStyle($sheet, "B{$row}:E{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(24);

                    // Add Dropdown for Kode TP
                    $this->addDropdown($sheet, "B{$row}", ['TP 1', 'TP 2', 'TP 3', 'TP 4']);
                    
                    // Add Dropdown for Kunci Jawaban
                    $this->addDropdown($sheet, "D{$row}", ['A', 'B', 'C', 'D', 'E']);

                    $row++;
                }

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(12);

                // Print Setup
                $this->setPrintArea($sheet, "A1:E" . max($row, 12));
            }
        ];
    }
}

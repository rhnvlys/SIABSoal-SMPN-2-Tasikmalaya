<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InputSiswaSheet extends BaseSheet
{
    public function __construct()
    {
        $this->title = 'INPUT_SISWA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_DATA);
                $this->setSheetFooter($sheet, 'INPUT SISWA');

                // Kop / Header
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', '👥  DAFTAR RENCANA DATA SISWA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1B365D'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(35);

                $sheet->mergeCells('A2:H2');
                $sheet->setCellValue('A2', 'SMP Negeri 2 Tasikmalaya - SIABSoal Administrasi Roster');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '4A5568']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Spacing
                $sheet->getRowDimension(3)->setRowHeight(10);

                // Table headers
                $headers = ['No', 'NIS *', 'NISN', 'Nama Siswa *', 'L/P *', 'Kelas *', 'Tahun Ajaran *', 'Status *'];
                foreach ($headers as $colIdx => $h) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '5';
                    $sheet->setCellValue($cell, $h);
                }
                $this->applyHeaderStyle($sheet, 'A5:H5');
                $sheet->getRowDimension(5)->setRowHeight(25);

                // Fill template rows (e.g. 100 empty rows)
                $maxRows = 100;
                for ($i = 1; $i <= $maxRows; $i++) {
                    $row = 5 + $i;
                    $sheet->setCellValue("A{$row}", $i);
                    
                    // Style first dummy row with values
                    if ($i === 1) {
                        $sheet->setCellValue("B{$row}", '2025001');
                        $sheet->setCellValue("C{$row}", '3200000001');
                        $sheet->setCellValue("D{$row}", 'Contoh Siswa');
                        $sheet->setCellValue("E{$row}", 'L');
                        $sheet->setCellValue("F{$row}", 'VII A');
                        $sheet->setCellValue("G{$row}", '2025/2026');
                        $sheet->setCellValue("H{$row}", 'aktif');
                    }

                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $this->applyBorder($sheet, "A{$row}:H{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(20);

                    // Add dropdown for L/P (Col E)
                    $validationJk = $sheet->getCell("E{$row}")->getDataValidation();
                    $validationJk->setType(DataValidation::TYPE_LIST);
                    $validationJk->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validationJk->setAllowBlank(false);
                    $validationJk->setShowInputMessage(true);
                    $validationJk->setShowErrorMessage(true);
                    $validationJk->setShowDropDown(true);
                    $validationJk->setFormula1('"L,P"');

                    // Add dropdown for Status (Col H)
                    $validationStatus = $sheet->getCell("H{$row}")->getDataValidation();
                    $validationStatus->setType(DataValidation::TYPE_LIST);
                    $validationStatus->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validationStatus->setAllowBlank(false);
                    $validationStatus->setShowInputMessage(true);
                    $validationStatus->setShowErrorMessage(true);
                    $validationStatus->setShowDropDown(true);
                    $validationStatus->setFormula1('"aktif,nonaktif,lulus"');
                }

                // Widths
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(10);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(15);
            }
        ];
    }
}

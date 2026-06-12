<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DataKelasSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'DATA_KELAS';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_DATA);
                $this->setSheetFooter($sheet, 'DATA KELAS');

                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', '🏫  DATA KELAS');
                $this->applyHeaderStyle($sheet, 'A1:E1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                $headers = ['kode_kelas', 'nama_kelas', 'tingkat', 'tahun_ajaran', 'wali_kelas'];
                foreach ($headers as $idx => $header) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 1) . '4', $header);
                }
                $this->applySubHeaderStyle($sheet, 'A4:E4');

                $kelasList = $this->ujian ? $this->ujian->kelas : collect();
                $row = 5;
                foreach ($kelasList as $idx => $kelas) {
                    $sheet->setCellValue("A{$row}", 'KLS-' . str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT));
                    $sheet->setCellValue("B{$row}", $kelas->nama_kelas);
                    $sheet->setCellValue("C{$row}", $kelas->tingkat);
                    $sheet->setCellValue("D{$row}", $kelas->tahunAjaran?->tahun_ajaran ?? $this->ujian?->tahunAjaran?->tahun_ajaran ?? '');
                    $sheet->setCellValue("E{$row}", $kelas->waliKelas?->nama_guru ?? '');
                    $this->applyInputStyle($sheet, "A{$row}:E{$row}");
                    $row++;
                }

                for ($i = 0; $i < 5; $i++, $row++) {
                    $sheet->setCellValue("A{$row}", 'KLS-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT));
                    $sheet->setCellValue("D{$row}", $this->ujian?->tahunAjaran?->tahun_ajaran ?? '');
                    $this->applyInputStyle($sheet, "A{$row}:E{$row}");
                }

                $sheet->freezePane('A5');
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(28);
                $sheet->getStyle('A5:E' . ($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Print Setup
                $this->setPrintArea($sheet, "A1:E" . max($row - 1, 12));
            },
        ];
    }
}

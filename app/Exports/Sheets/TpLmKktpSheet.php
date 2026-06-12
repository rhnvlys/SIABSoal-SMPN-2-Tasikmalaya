<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TpLmKktpSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'TP_LM_KKTP';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_IDENTITAS);
                $this->setSheetFooter($sheet, 'TP / LM / KKTP');

                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', '🎯  TUJUAN PEMBELAJARAN, LINGKUP MATERI, DAN KKTP');
                $this->applyHeaderStyle($sheet, 'A1:E1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                $headers = ['kode_tp', 'tujuan_pembelajaran', 'lingkup_materi', 'kktp', 'nomor_soal_terkait'];
                foreach ($headers as $idx => $header) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 1) . '4', $header);
                }
                $this->applySubHeaderStyle($sheet, 'A4:E4');

                $kktp = (int) ($this->ujian?->kktp_value ?? 75);
                $rows = [
                    ['TP 1', $this->ujian?->tujuan_pembelajaran ?: '', $this->ujian?->lingkup_materi ?: '', $kktp, '1-' . min(5, (int) ($this->ujian?->jumlah_soal ?? 10))],
                    ['TP 2', '', '', $kktp, ''],
                    ['TP 3', '', '', $kktp, ''],
                    ['TP 4', '', '', $kktp, ''],
                ];

                $row = 5;
                foreach ($rows as $data) {
                    foreach ($data as $idx => $value) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 1) . $row, $value);
                    }
                    $this->addWholeNumberValidation($sheet, "D{$row}", 0, 100);
                    $this->applyInputStyle($sheet, "A{$row}:E{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(42);
                    $row++;
                }

                $sheet->freezePane('A5');
                $sheet->getStyle('A5:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D5:E' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(46);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(22);
            },
        ];
    }
}

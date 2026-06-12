<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OlahT2Sheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'OLAH_T2';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_HASIL);
                $this->setSheetFooter($sheet, 'OLAH T2');

                $pesertaCount = $this->ujian ? max($this->ujian->pesertaUjian()->count(), 5) : 5;

                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', '🔄  OLAH T2 — RANKING DAN KELOMPOK SISWA');
                $this->applyHeaderStyle($sheet, 'A1:H1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                $headers = ['ranking', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'nilai', 'kelompok'];
                foreach ($headers as $idx => $header) {
                    $sheet->setCellValue(chr(65 + $idx) . '4', $header);
                }
                $this->applySubHeaderStyle($sheet, 'A4:H4');

                $startRow = 5;
                $endRow = $startRow + $pesertaCount - 1;
                for ($i = 0; $i < $pesertaCount; $i++) {
                    $row = $startRow + $i;
                    $refRow = $startRow + $i;
                    $sheet->setCellValue("A{$row}", "=IF(G{$row}=\"\",\"\",RANK.EQ(G{$row},G{$startRow}:G{$endRow},0))");
                    $sheet->setCellValue("B{$row}", "=HASIL_T1!B{$refRow}");
                    $sheet->setCellValue("C{$row}", "=HASIL_T1!C{$refRow}");
                    $sheet->setCellValue("D{$row}", "=HASIL_T1!D{$refRow}");
                    $sheet->setCellValue("E{$row}", "=HASIL_T1!E{$refRow}");
                    $sheet->setCellValue("F{$row}", "=HASIL_T1!F{$refRow}");
                    $sheet->setCellValue("G{$row}", "=HASIL_T1!" . $this->nilaiColumn() . "{$refRow}");
                    $sheet->setCellValue("H{$row}", "=IF(F{$row}<>\"hadir\",\"tengah\",IF(A{$row}<=ROUNDUP(COUNTA(D{$startRow}:D{$endRow})*0.27,0),\"atas\",IF(A{$row}>COUNTA(D{$startRow}:D{$endRow})-ROUNDUP(COUNTA(D{$startRow}:D{$endRow})*0.27,0),\"bawah\",\"tengah\")))");
                    $this->applyBorder($sheet, "A{$row}:H{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(24);
                }

                $sheet->freezePane('A5');
                foreach (['A' => 10, 'B' => 16, 'C' => 18, 'D' => 32, 'E' => 14, 'F' => 18, 'G' => 12, 'H' => 14] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
                $sheet->getStyle("A{$startRow}:H{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$startRow}:D{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }

    private function nilaiColumn(): string
    {
        $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
        $index = 6 + $jumlahSoal + 3;
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }
}

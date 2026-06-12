<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class HasilT1Sheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'HASIL_T1';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_HASIL);
                $this->setSheetFooter($sheet, 'HASIL T1');

                $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
                $kktp = (int) ($this->ujian?->kktp_value ?? 75);

                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', '📊  HASIL T1 — KONVERSI JAWABAN MENJADI SKOR 0/1');
                $this->applyHeaderStyle($sheet, 'A1:J1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                $headers = ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];
                for ($i = 1; $i <= $jumlahSoal; $i++) {
                    $headers[] = 'skor_' . $i;
                }
                $headers = array_merge($headers, ['jumlah_benar', 'jumlah_salah', 'nilai', 'keterangan']);

                foreach ($headers as $idx => $header) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 1) . '4', $header);
                }

                $lastCol = Coordinate::stringFromColumnIndex(count($headers));
                $this->applySubHeaderStyle($sheet, "A4:{$lastCol}4");

                $pesertaCount = $this->ujian ? max($this->ujian->pesertaUjian()->count(), 5) : 5;
                $benarCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 1);
                $salahCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 2);
                $nilaiCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 3);
                $ketCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 4);
                $firstSkorCol = Coordinate::stringFromColumnIndex(7);
                $lastSkorCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal);

                for ($i = 0; $i < $pesertaCount; $i++) {
                    $row = 5 + $i;
                    $refRow = 7 + $i;
                    $sheet->setCellValue("A{$row}", $i + 1);
                    $sheet->setCellValue("B{$row}", "=INPUT_SKOR_01!B{$refRow}");
                    $sheet->setCellValue("C{$row}", "=INPUT_SKOR_01!C{$refRow}");
                    $sheet->setCellValue("D{$row}", "=INPUT_SKOR_01!D{$refRow}");
                    $sheet->setCellValue("E{$row}", "=INPUT_SKOR_01!E{$refRow}");
                    $sheet->setCellValue("F{$row}", "=INPUT_SKOR_01!F{$refRow}");

                    for ($q = 1; $q <= $jumlahSoal; $q++) {
                        $col = Coordinate::stringFromColumnIndex(6 + $q);
                        $sheet->setCellValue("{$col}{$row}", "=INPUT_SKOR_01!{$col}{$refRow}");
                    }

                    $sheet->setCellValue("{$benarCol}{$row}", "=SUM({$firstSkorCol}{$row}:{$lastSkorCol}{$row})");
                    $sheet->setCellValue("{$salahCol}{$row}", "={$jumlahSoal}-{$benarCol}{$row}");
                    $sheet->setCellValue("{$nilaiCol}{$row}", "=IF({$jumlahSoal}=0,0,{$benarCol}{$row}/{$jumlahSoal}*100)");
                    $sheet->setCellValue("{$ketCol}{$row}", "=IF(F{$row}=\"tidak_hadir\",\"TIDAK HADIR\",IF({$nilaiCol}{$row}>={$kktp},\"TERCAPAI\",\"PERLU PENINGKATAN\"))");
                    $this->applyBorder($sheet, "A{$row}:{$lastCol}{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(24);
                }

                $sheet->freezePane('G5');
                foreach (['A' => 8, 'B' => 16, 'C' => 18, 'D' => 32, 'E' => 14, 'F' => 18] as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
                for ($q = 1; $q <= $jumlahSoal; $q++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(6 + $q))->setWidth(9);
                }
                $sheet->getColumnDimension($benarCol)->setWidth(15);
                $sheet->getColumnDimension($salahCol)->setWidth(15);
                $sheet->getColumnDimension($nilaiCol)->setWidth(12);
                $sheet->getColumnDimension($ketCol)->setWidth(22);
                $sheet->getStyle('A5:' . $lastCol . (4 + $pesertaCount))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D5:D' . (4 + $pesertaCount))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}

<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DataImportSystemSheet extends BaseSheet
{
    public function __construct(
        private readonly string $type,
        private readonly ?Ujian $ujian,
        private readonly string $sheetTitle = 'DATA_IMPORT_SYSTEM'
    ) {
        $this->title = $sheetTitle;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_SYSTEM);

                $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
                $pesertaList = $this->ujian
                    ? $this->ujian->pesertaUjian()
                        ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama_siswa')
                        ->select('peserta_ujian.*')
                        ->get()
                    : collect();
                $pesertaCount = max($pesertaList->count(), 5);

                if ($this->type === 'kunci-jawaban' || $this->type === 'kunci_jawaban') {
                    // Columns: nomor_soal, kunci_jawaban, bobot
                    $sheet->setCellValue('A1', 'nomor_soal');
                    $sheet->setCellValue('B1', 'kunci_jawaban');
                    $sheet->setCellValue('C1', 'bobot');

                    $this->applyHeaderStyle($sheet, 'A1:C1');
                    $sheet->getRowDimension(1)->setRowHeight(24);

                    for ($i = 1; $i <= $jumlahSoal; $i++) {
                        $row = $i + 1;
                        $refRow = 6 + $i; // data starts at row 7 in KUNCI_JAWABAN
                        $sheet->setCellValue("A{$row}", "=KUNCI_JAWABAN!A{$refRow}");
                        $sheet->setCellValue("B{$row}", "=KUNCI_JAWABAN!D{$refRow}");
                        $sheet->setCellValue("C{$row}", "=KUNCI_JAWABAN!E{$refRow}");

                        $this->applyBorder($sheet, "A{$row}:C{$row}");
                        $sheet->getRowDimension($row)->setRowHeight(20);
                    }

                    $sheet->getColumnDimension('A')->setWidth(15);
                    $sheet->getColumnDimension('B')->setWidth(15);
                    $sheet->getColumnDimension('C')->setWidth(12);

                } elseif ($this->type === 'jawaban-abcd' || $this->type === 'jawaban_abcd' || $this->type === 'data-mentah-t1') {
                    // Columns: nis, nisn, nama_siswa, jenis_kelamin, status_kehadiran, soal_1...soal_n
                    $headings = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];
                    for ($i = 1; $i <= $jumlahSoal; $i++) {
                        $headings[] = 'soal_' . $i;
                    }

                    foreach ($headings as $colIdx => $h) {
                        $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
                        $sheet->setCellValue($cell, $h);
                    }

                    $lastColLetter = Coordinate::stringFromColumnIndex(count($headings));
                    $this->applyHeaderStyle($sheet, "A1:{$lastColLetter}1");
                    $sheet->getRowDimension(1)->setRowHeight(24);

                    for ($r = 1; $r <= $pesertaCount; $r++) {
                        $row = $r + 1;
                        $refRow = 6 + $r; // starts at row 7 in INPUT_JAWABAN_ABCD
                        
                        $sheet->setCellValue("A{$row}", "=INPUT_JAWABAN_ABCD!B{$refRow}");
                        $sheet->setCellValue("B{$row}", "=INPUT_JAWABAN_ABCD!C{$refRow}");
                        $sheet->setCellValue("C{$row}", "=INPUT_JAWABAN_ABCD!D{$refRow}");
                        $sheet->setCellValue("D{$row}", "=INPUT_JAWABAN_ABCD!E{$refRow}");
                        $sheet->setCellValue("E{$row}", "=INPUT_JAWABAN_ABCD!F{$refRow}");

                        for ($j = 1; $j <= $jumlahSoal; $j++) {
                            $targetColLetter = Coordinate::stringFromColumnIndex(6 + $j);
                            $colLetter = Coordinate::stringFromColumnIndex(5 + $j);
                            $sheet->setCellValue($colLetter . $row, "=INPUT_JAWABAN_ABCD!{$targetColLetter}{$refRow}");
                        }

                        $this->applyBorder($sheet, "A{$row}:{$lastColLetter}{$row}");
                        $sheet->getRowDimension($row)->setRowHeight(20);
                    }

                } elseif ($this->type === 'skor-01' || $this->type === 'skor_01' || $this->type === 'skor-0/1' || $this->type === 'skor_0_1') {
                    // Columns: nis, nisn, nama_siswa, jenis_kelamin, status_kehadiran, skor_1...skor_n
                    $headings = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];
                    for ($i = 1; $i <= $jumlahSoal; $i++) {
                        $headings[] = 'skor_' . $i;
                    }

                    foreach ($headings as $colIdx => $h) {
                        $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
                        $sheet->setCellValue($cell, $h);
                    }

                    $lastColLetter = Coordinate::stringFromColumnIndex(count($headings));
                    $this->applyHeaderStyle($sheet, "A1:{$lastColLetter}1");
                    $sheet->getRowDimension(1)->setRowHeight(24);

                    for ($r = 1; $r <= $pesertaCount; $r++) {
                        $row = $r + 1;
                        $refRow = 6 + $r; // starts at row 7 in INPUT_SKOR_01
                        
                        $sheet->setCellValue("A{$row}", "=INPUT_SKOR_01!B{$refRow}");
                        $sheet->setCellValue("B{$row}", "=INPUT_SKOR_01!C{$refRow}");
                        $sheet->setCellValue("C{$row}", "=INPUT_SKOR_01!D{$refRow}");
                        $sheet->setCellValue("D{$row}", "=INPUT_SKOR_01!E{$refRow}");
                        $sheet->setCellValue("E{$row}", "=INPUT_SKOR_01!F{$refRow}");

                        for ($j = 1; $j <= $jumlahSoal; $j++) {
                            $targetColLetter = Coordinate::stringFromColumnIndex(6 + $j);
                            $colLetter = Coordinate::stringFromColumnIndex(5 + $j);
                            $sheet->setCellValue($colLetter . $row, "=INPUT_SKOR_01!{$targetColLetter}{$refRow}");
                        }

                        $this->applyBorder($sheet, "A{$row}:{$lastColLetter}{$row}");
                        $sheet->getRowDimension($row)->setRowHeight(20);
                    }
                } else {
                    // Fallback / Data Siswa
                    $headings = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'];
                    foreach ($headings as $colIdx => $h) {
                        $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
                        $sheet->setCellValue($cell, $h);
                    }
                    $lastColLetter = Coordinate::stringFromColumnIndex(count($headings));
                    $this->applyHeaderStyle($sheet, "A1:{$lastColLetter}1");
                    $sheet->getRowDimension(1)->setRowHeight(24);
                    
                    for ($r = 1; $r <= 100; $r++) {
                        $row = $r + 1;
                        $refRow = 5 + $r; // starts at row 6 in INPUT_SISWA
                        $sheet->setCellValue("A{$row}", "=INPUT_SISWA!B{$refRow}");
                        $sheet->setCellValue("B{$row}", "=INPUT_SISWA!C{$refRow}");
                        $sheet->setCellValue("C{$row}", "=INPUT_SISWA!D{$refRow}");
                        $sheet->setCellValue("D{$row}", "=INPUT_SISWA!E{$refRow}");
                        $sheet->setCellValue("E{$row}", "=INPUT_SISWA!F{$refRow}");
                        $sheet->setCellValue("F{$row}", "=INPUT_SISWA!G{$refRow}");
                        $sheet->setCellValue("G{$row}", "=INPUT_SISWA!H{$refRow}");

                        $this->applyBorder($sheet, "A{$row}:G{$row}");
                        $sheet->getRowDimension($row)->setRowHeight(20);
                    }
                }
            }
        ];
    }
}

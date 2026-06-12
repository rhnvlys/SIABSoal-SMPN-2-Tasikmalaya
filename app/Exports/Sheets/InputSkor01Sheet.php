<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InputSkor01Sheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'INPUT_SKOR_01';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_INPUT);
                $this->setSheetFooter($sheet, 'INPUT SKOR 0/1');

                // ── Heading ───────────────────────────────────────────
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', '🔢  INPUT SKOR SISWA (BINER 0/1)');
                $this->applyHeaderStyle($sheet, 'A1:G1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // ── Ujian Metadata ────────────────────────────────────
                $sheet->setCellValue('A3', 'Mata Pelajaran:');
                $sheet->setCellValue('B3', $this->ujian?->mapel?->nama_mapel ?? '-');
                $sheet->setCellValue('D3', 'Kelas:');
                $sheet->setCellValue('E3', $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-');

                $sheet->setCellValue('A4', 'Nama Ujian:');
                $sheet->setCellValue('B4', $this->ujian?->nama_ujian ?? '-');
                $sheet->setCellValue('D4', 'Jumlah Soal:');
                $sheet->setCellValue('E4', $this->ujian?->jumlah_soal ?? 10);

                $this->applyMetaLabel($sheet, 'A3');
                $this->applyMetaLabel($sheet, 'A4');
                $this->applyMetaLabel($sheet, 'D3');
                $this->applyMetaLabel($sheet, 'D4');

                $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
                $kktp = $this->ujian?->kktp_value ?? 75;

                // ── Table Headers ─────────────────────────────────────
                $headers = ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];
                foreach ($headers as $colIdx => $header) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '6';
                    $sheet->setCellValue($cell, $header);
                }

                for ($i = 1; $i <= $jumlahSoal; $i++) {
                    $colName = Coordinate::stringFromColumnIndex(6 + $i);
                    $sheet->setCellValue($colName . '6', 'skor_' . $i);
                }

                $benarColNum = 6 + $jumlahSoal + 1;
                $nilaiColNum = 6 + $jumlahSoal + 2;
                $ketColNum = 6 + $jumlahSoal + 3;

                $benarColLetter = Coordinate::stringFromColumnIndex($benarColNum);
                $nilaiColLetter = Coordinate::stringFromColumnIndex($nilaiColNum);
                $ketColLetter = Coordinate::stringFromColumnIndex($ketColNum);

                $sheet->setCellValue($benarColLetter . '6', 'jumlah_benar');
                $sheet->setCellValue($nilaiColLetter . '6', 'nilai');
                $sheet->setCellValue($ketColLetter . '6', 'keterangan');

                $totalColumns = $ketColNum;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumns);

                $this->applySubHeaderStyle($sheet, "A6:{$lastColumnLetter}6");
                $sheet->getRowDimension(6)->setRowHeight(28);

                // ── Freeze Panes ──────────────────────────────────────
                $sheet->freezePane('G7');

                // ── Data Rows ─────────────────────────────────────────
                $pesertaList = $this->ujian
                    ? $this->ujian->pesertaUjian()
                        ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama_siswa')
                        ->select('peserta_ujian.*')
                        ->get()
                    : collect();

                $row = 7;
                $idx = 1;
                $firstSkorCol = Coordinate::stringFromColumnIndex(7);
                $lastSkorCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal);

                foreach ($pesertaList as $p) {
                    $this->writeDataRow($sheet, $row, $idx, $p, $jumlahSoal, $kktp,
                        $benarColLetter, $nilaiColLetter, $ketColLetter, $lastColumnLetter,
                        $firstSkorCol, $lastSkorCol, true);
                    $row++;
                    $idx++;
                }

                // ── Placeholder rows ──────────────────────────────────
                if ($pesertaList->isEmpty()) {
                    for ($k = 0; $k < 5; $k++) {
                        $this->writeDataRow($sheet, $row, $idx, null, $jumlahSoal, $kktp,
                            $benarColLetter, $nilaiColLetter, $ketColLetter, $lastColumnLetter,
                            $firstSkorCol, $lastSkorCol, false);
                        $row++;
                        $idx++;
                    }
                }

                $lastDataRow = $row - 1;

                // ── Zebra Striping ────────────────────────────────────
                $this->applyAlternatingRows($sheet, 7, $lastDataRow, 'A', Coordinate::stringFromColumnIndex(6));

                // ── Column Dimensions ─────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(8);
                $sheet->getColumnDimension('F')->setWidth(18);

                for ($i = 1; $i <= $jumlahSoal; $i++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(6 + $i))->setWidth(9);
                }
                $sheet->getColumnDimension($benarColLetter)->setWidth(15);
                $sheet->getColumnDimension($nilaiColLetter)->setWidth(12);
                $sheet->getColumnDimension($ketColLetter)->setWidth(22);

                // ── Print Setup ───────────────────────────────────────
                $this->setPrintArea($sheet, "A1:{$lastColumnLetter}{$lastDataRow}");
            }
        ];
    }

    private function writeDataRow($sheet, int $row, int $idx, $peserta, int $jumlahSoal, $kktp,
        string $benarCol, string $nilaiCol, string $ketCol, string $lastCol,
        string $firstSkorCol, string $lastSkorCol, bool $hasData): void
    {
        $sheet->setCellValue("A{$row}", $idx);

        if ($hasData && $peserta) {
            $sheet->setCellValue("B{$row}", $peserta->siswa->nis ?? '');
            $sheet->setCellValue("C{$row}", $peserta->siswa->nisn ?? '');
            $sheet->setCellValue("D{$row}", $peserta->siswa->nama_siswa ?? '');
            $sheet->setCellValue("E{$row}", $peserta->siswa->jenis_kelamin ?? '');
            $sheet->setCellValue("F{$row}", $peserta->status_kehadiran ?? 'hadir');

            $jawabanMap = $peserta->jawabanSiswa->keyBy('soal_id');
            $soalList = $this->ujian ? $this->ujian->soal->sortBy('nomor_soal')->values() : collect();

            for ($i = 1; $i <= $jumlahSoal; $i++) {
                $colLetter = Coordinate::stringFromColumnIndex(6 + $i);
                $soalObj = $soalList->get($i - 1);
                $val = '';
                if ($soalObj) {
                    $js = $jawabanMap->get($soalObj->id);
                    $val = $js ? $js->skor_biner : '';
                }
                $sheet->setCellValue($colLetter . $row, $val);
                $this->addDropdown($sheet, $colLetter . $row, ['0', '1']);
                $this->applyScoreCell($sheet, $colLetter . $row, $val);
            }
        } else {
            $sheet->setCellValue("F{$row}", 'hadir');
            for ($i = 1; $i <= $jumlahSoal; $i++) {
                $colLetter = Coordinate::stringFromColumnIndex(6 + $i);
                $this->addDropdown($sheet, $colLetter . $row, ['0', '1']);
                $this->applyInputStyle($sheet, $colLetter . $row);
                $sheet->getStyle($colLetter . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // ── Dropdown Kehadiran ────────────────────────────────────
        $this->addDropdown($sheet, "F{$row}", ['hadir', 'tidak_hadir', 'izin', 'sakit', 'alfa']);

        // ── Formulas ─────────────────────────────────────────────
        $sheet->setCellValue($benarCol . $row, "=SUM({$firstSkorCol}{$row}:{$lastSkorCol}{$row})");
        $sheet->setCellValue($nilaiCol . $row, "=({$benarCol}{$row}/{$jumlahSoal})*100");
        $sheet->setCellValue($ketCol . $row, "=IF(F{$row}=\"tidak_hadir\",\"TIDAK HADIR\",IF({$nilaiCol}{$row}>={$kktp},\"TERCAPAI\",\"PERLU PENINGKATAN\"))");

        // ── Center Alignments ────────────────────────────────────
        foreach (['A', 'B', 'C', 'E', 'F'] as $c) {
            $sheet->getStyle("{$c}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle($benarCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($nilaiCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($ketCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyBorder($sheet, "A{$row}:{$lastCol}{$row}");
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    /**
     * Color-code skor cells: green for 1 (benar), red/pink for 0 (salah)
     */
    private function applyScoreCell($sheet, string $cell, $value): void
    {
        if ($value === 1 || $value === '1') {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::CLR_GREEN_TEXT]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_GREEN_BG]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        } elseif ($value === 0 || $value === '0') {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::CLR_RED_TEXT]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_RED_BG]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        } else {
            $this->applyInputStyle($sheet, $cell);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}

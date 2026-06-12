<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InputJawabanABCDSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'INPUT_JAWABAN_ABCD';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_INPUT);
                $this->setSheetFooter($sheet, 'INPUT JAWABAN ABCD');

                // ── Heading ───────────────────────────────────────────
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', '✏️  INPUT JAWABAN SISWA (PILIHAN GANDA A/B/C/D/E)');
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

                // ── Table Headers ─────────────────────────────────────
                $headers = ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];
                foreach ($headers as $colIdx => $header) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '6';
                    $sheet->setCellValue($cell, $header);
                }

                for ($i = 1; $i <= $jumlahSoal; $i++) {
                    $colName = Coordinate::stringFromColumnIndex(6 + $i);
                    $sheet->setCellValue($colName . '6', 'soal_' . $i);
                }

                $terisiColNum = 6 + $jumlahSoal + 1;
                $validColNum = 6 + $jumlahSoal + 2;
                $terisiColLetter = Coordinate::stringFromColumnIndex($terisiColNum);
                $validColLetter = Coordinate::stringFromColumnIndex($validColNum);

                $sheet->setCellValue($terisiColLetter . '6', 'jumlah_terisi');
                $sheet->setCellValue($validColLetter . '6', 'catatan_validasi');

                $totalColumns = $validColNum;
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
                $firstSoalCol = Coordinate::stringFromColumnIndex(7);
                $lastSoalCol = Coordinate::stringFromColumnIndex(6 + $jumlahSoal);

                foreach ($pesertaList as $p) {
                    $this->writeRow($sheet, $row, $idx, $p, $jumlahSoal,
                        $terisiColLetter, $validColLetter, $lastColumnLetter,
                        $firstSoalCol, $lastSoalCol, true);
                    $row++;
                    $idx++;
                }

                if ($pesertaList->isEmpty()) {
                    for ($k = 0; $k < 5; $k++) {
                        $this->writeRow($sheet, $row, $idx, null, $jumlahSoal,
                            $terisiColLetter, $validColLetter, $lastColumnLetter,
                            $firstSoalCol, $lastSoalCol, false);
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
                $sheet->getColumnDimension($terisiColLetter)->setWidth(15);
                $sheet->getColumnDimension($validColLetter)->setWidth(20);

                // ── Print Setup ───────────────────────────────────────
                $this->setPrintArea($sheet, "A1:{$lastColumnLetter}{$lastDataRow}");
            }
        ];
    }

    private function writeRow($sheet, int $row, int $idx, $peserta, int $jumlahSoal,
        string $terisiCol, string $validCol, string $lastCol,
        string $firstSoalCol, string $lastSoalCol, bool $hasData): void
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
                    $val = $js ? $js->jawaban : '';
                }
                $sheet->setCellValue($colLetter . $row, $val);
                $this->addDropdown($sheet, $colLetter . $row, ['A', 'B', 'C', 'D', 'E']);
                $this->applyInputStyle($sheet, $colLetter . $row);
                $sheet->getStyle($colLetter . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        } else {
            $sheet->setCellValue("F{$row}", 'hadir');
            for ($i = 1; $i <= $jumlahSoal; $i++) {
                $colLetter = Coordinate::stringFromColumnIndex(6 + $i);
                $this->addDropdown($sheet, $colLetter . $row, ['A', 'B', 'C', 'D', 'E']);
                $this->applyInputStyle($sheet, $colLetter . $row);
                $sheet->getStyle($colLetter . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        $this->addDropdown($sheet, "F{$row}", ['hadir', 'tidak_hadir', 'izin', 'sakit', 'alfa']);

        // ── Formulas ─────────────────────────────────────────────
        $sheet->setCellValue($terisiCol . $row, "=COUNTA({$firstSoalCol}{$row}:{$lastSoalCol}{$row})");
        $sheet->setCellValue($validCol . $row, "=IF(F{$row}=\"tidak_hadir\",\"TIDAK HADIR\",IF({$terisiCol}{$row}={$jumlahSoal},\"LENGKAP\",\"BELUM LENGKAP\"))");

        // ── Alignments ───────────────────────────────────────────
        foreach (['A', 'B', 'C', 'E', 'F'] as $c) {
            $sheet->getStyle("{$c}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle($terisiCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($validCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyBorder($sheet, "A{$row}:{$lastCol}{$row}");
        $sheet->getRowDimension($row)->setRowHeight(24);
    }
}

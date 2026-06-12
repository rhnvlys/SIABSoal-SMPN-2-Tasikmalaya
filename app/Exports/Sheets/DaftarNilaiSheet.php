<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DaftarNilaiSheet extends BaseSheet
{
    public function __construct(
        private readonly ?Ujian $ujian,
        private readonly string $sheetTitle = 'DAFTAR_NILAI'
    )
    {
        $this->title = $sheetTitle;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_NILAI);
                $this->setSheetFooter($sheet, 'DAFTAR NILAI T4');

                // Heading
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', '📝  DAFTAR NILAI EVALUASI BELAJAR');
                $this->applyHeaderStyle($sheet, 'A1:J1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // School header
                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue('A2', 'SMP NEGERI 2 TASIKMALAYA');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1B365D']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Ujian details
                $sheet->setCellValue('A4', 'Mata Pelajaran:');
                $sheet->setCellValue('B4', $this->ujian?->mapel?->nama_mapel ?? '-');
                $sheet->setCellValue('F4', 'Kelas:');
                $sheet->setCellValue('G4', $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-');

                $sheet->setCellValue('A5', 'Nama Ujian:');
                $sheet->setCellValue('B5', $this->ujian?->nama_ujian ?? '-');
                $sheet->setCellValue('F5', 'Semester:');
                $sheet->setCellValue('G5', $this->ujian?->tahunAjaran?->semester ?? '-');

                $sheet->setCellValue('A6', 'Nama Guru:');
                $sheet->setCellValue('B6', $this->ujian?->guru?->nama_guru ?? '-');
                $sheet->setCellValue('F6', 'Tahun Ajaran:');
                $sheet->setCellValue('G6', $this->ujian?->tahunAjaran?->tahun_ajaran ?? '-');

                $sheet->setCellValue('A7', 'KKTP/KKM:');
                $sheet->setCellValue('B7', $this->ujian?->kktp_value ?? 75);
                $sheet->setCellValue('F7', 'Tanggal Cetak:');
                $sheet->setCellValue('G7', now()->format('d/m/Y H:i'));

                $this->applyMetaLabel($sheet, 'A4');
                $this->applyMetaLabel($sheet, 'A5');
                $this->applyMetaLabel($sheet, 'A6');
                $this->applyMetaLabel($sheet, 'A7');
                $this->applyMetaLabel($sheet, 'F4');
                $this->applyMetaLabel($sheet, 'F5');
                $this->applyMetaLabel($sheet, 'F6');
                $this->applyMetaLabel($sheet, 'F7');

                // Table Headers
                $headers = ['No', 'NIS', 'NISN', 'Nama Murid', 'L/P', 'Status Kehadiran', 'Jumlah Benar', 'Jumlah Salah', 'Nilai', 'Keterangan'];
                foreach ($headers as $colIdx => $header) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '9';
                    $sheet->setCellValue($cell, $header);
                }
                $this->applySubHeaderStyle($sheet, 'A9:J9');
                $sheet->getRowDimension(9)->setRowHeight(25);

                $pesertaList = $this->ujian
                    ? $this->ujian->pesertaUjian()
                        ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama_siswa')
                        ->select('peserta_ujian.*')
                        ->get()
                    : collect();
                
                $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
                
                // Let's determine column letters on INPUT_SKOR_01
                $benarColLetter = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 1);
                $nilaiColLetter = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 2);
                $ketColLetter = Coordinate::stringFromColumnIndex(6 + $jumlahSoal + 3);

                $row = 10;
                $idx = 1;
                $startRow = $row;

                foreach ($pesertaList as $p) {
                    $sheet->setCellValue("A{$row}", $idx);
                    
                    // Formulas pointing to INPUT_SKOR_01 (starts at row 7)
                    $refRow = $row - 3;
                    $sheet->setCellValue("B{$row}", "=INPUT_SKOR_01!B{$refRow}");
                    $sheet->setCellValue("C{$row}", "=INPUT_SKOR_01!C{$refRow}");
                    $sheet->setCellValue("D{$row}", "=INPUT_SKOR_01!D{$refRow}");
                    $sheet->setCellValue("E{$row}", "=INPUT_SKOR_01!E{$refRow}");
                    $sheet->setCellValue("F{$row}", "=INPUT_SKOR_01!F{$refRow}");
                    $sheet->setCellValue("G{$row}", "=INPUT_SKOR_01!{$benarColLetter}{$refRow}");
                    $sheet->setCellValue("H{$row}", "={$jumlahSoal}-G{$row}");
                    $sheet->setCellValue("I{$row}", "=INPUT_SKOR_01!{$nilaiColLetter}{$refRow}");
                    $sheet->setCellValue("J{$row}", "=INPUT_SKOR_01!{$ketColLetter}{$refRow}");

                    // Alignments
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $this->applyBorder($sheet, "A{$row}:J{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(24);

                    $row++;
                    $idx++;
                }

                if ($pesertaList->isEmpty()) {
                    for ($k = 0; $k < 5; $k++) {
                        $sheet->setCellValue("A{$row}", $idx);
                        $refRow = $row - 3;
                        $sheet->setCellValue("B{$row}", "=INPUT_SKOR_01!B{$refRow}");
                        $sheet->setCellValue("C{$row}", "=INPUT_SKOR_01!C{$refRow}");
                        $sheet->setCellValue("D{$row}", "=INPUT_SKOR_01!D{$refRow}");
                        $sheet->setCellValue("E{$row}", "=INPUT_SKOR_01!E{$refRow}");
                        $sheet->setCellValue("F{$row}", "=INPUT_SKOR_01!F{$refRow}");
                        $sheet->setCellValue("G{$row}", "=INPUT_SKOR_01!{$benarColLetter}{$refRow}");
                        $sheet->setCellValue("H{$row}", "={$jumlahSoal}-G{$row}");
                        $sheet->setCellValue("I{$row}", "=INPUT_SKOR_01!{$nilaiColLetter}{$refRow}");
                        $sheet->setCellValue("J{$row}", "=INPUT_SKOR_01!{$ketColLetter}{$refRow}");

                        $this->applyBorder($sheet, "A{$row}:J{$row}");
                        $sheet->getRowDimension($row)->setRowHeight(24);
                        $row++;
                        $idx++;
                    }
                }

                $endRow = $row - 1;

                // Spacing
                $row += 2;

                // Summary stats at bottom
                $sheet->mergeCells("A{$row}:D" . ($row + 7));
                $sheet->setCellValue("A{$row}", "STATISTIK HASIL EVALUASI");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1B365D']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ]
                ]);
                $this->applyBorder($sheet, "A{$row}:D" . ($row + 7));

                $stats = [
                    'Jumlah Siswa' => "=COUNTA(D{$startRow}:D{$endRow})",
                    'Siswa Hadir' => "=COUNTIF(F{$startRow}:F{$endRow}, \"hadir\")",
                    'Siswa Tidak Hadir' => "=COUNTIF(F{$startRow}:F{$endRow}, \"tidak_hadir\") + COUNTIF(F{$startRow}:F{$endRow}, \"izin\") + COUNTIF(F{$startRow}:F{$endRow}, \"sakit\") + COUNTIF(F{$startRow}:F{$endRow}, \"alfa\")",
                    'Nilai Tertinggi' => "=MAX(I{$startRow}:I{$endRow})",
                    'Nilai Terendah' => "=MIN(I{$startRow}:I{$endRow})",
                    'Rata-rata Kelas' => "=AVERAGE(I{$startRow}:I{$endRow})",
                    'Ketuntasan (Tercapai)' => "=COUNTIF(J{$startRow}:J{$endRow}, \"TERCAPAI\")",
                    'Perlu Peningkatan' => "=COUNTIF(J{$startRow}:J{$endRow}, \"PERLU PENINGKATAN\")",
                ];

                $statRow = $row;
                foreach ($stats as $label => $formula) {
                    $sheet->setCellValue("E{$statRow}", $label);
                    $sheet->setCellValue("F{$statRow}", $formula);
                    $sheet->mergeCells("F{$statRow}:J{$statRow}");

                    $sheet->getStyle("E{$statRow}")->getFont()->setBold(true);
                    $sheet->getStyle("F{$statRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    $this->applyBorder($sheet, "E{$statRow}:J{$statRow}");
                    $sheet->getRowDimension($statRow)->setRowHeight(22);
                    $statRow++;
                }

                // Column dimensions
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(25);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(22);
            }
        ];
    }
}

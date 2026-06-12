<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BerandaSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'BERANDA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_BERANDA);
                $this->setSheetFooter($sheet, 'BERANDA');

                // ── Title Banner ──────────────────────────────────────────
                $sheet->mergeCells('A1:I2');
                $sheet->setCellValue('A1', '📊  Template Administrasi Penilaian SIABSoal');
                $sheet->getStyle('A1:I2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_NAVY]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(30);

                // ── Subtitle ──────────────────────────────────────────────
                $sheet->mergeCells('A3:I3');
                $sheet->setCellValue('A3', 'Sistem Informasi Analisis Butir Soal Berbasis Web — SMP Negeri 2 Tasikmalaya');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => self::CLR_TEXT_SECONDARY]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_BLUE_SOFT]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(24);

                // ── Info Block (Left) ─────────────────────────────────────
                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', '📋 Informasi Template');
                $this->applySectionTitle($sheet, 'A5:D5', 'Informasi Template');
                $sheet->getRowDimension(5)->setRowHeight(26);

                $info = [
                    ['Nama Sistem', 'SIABSoal SMPN 2 Tasikmalaya'],
                    ['Nama Lengkap', 'Sistem Informasi Analisis Butir Soal Berbasis Web'],
                    ['Ujian', $this->ujian?->nama_ujian ?? '-'],
                    ['Guru Pengampu', $this->ujian?->guru?->nama_guru ?? '-'],
                    ['Kelas', $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-'],
                    ['Tahun Ajaran', $this->ujian?->tahunAjaran?->tahun_ajaran ?? '-'],
                    ['KKTP/KKM', $this->ujian?->kktp_value ?? 75],
                ];

                $row = 6;
                foreach ($info as $idx => [$label, $value]) {
                    $sheet->setCellValue("A{$row}", $label);
                    $sheet->setCellValue("B{$row}", $value);
                    $sheet->mergeCells("B{$row}:D{$row}");
                    $this->applyMetaLabel($sheet, "A{$row}");
                    $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);
                    $this->applyBorder($sheet, "A{$row}:D{$row}");

                    // Zebra
                    if ($idx % 2 === 1) {
                        $sheet->getStyle("A{$row}:D{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::CLR_ZEBRA_EVEN],
                        ]);
                    }

                    $sheet->getRowDimension($row)->setRowHeight(24);
                    $row++;
                }

                // ── Steps Block (Right) ───────────────────────────────────
                $sheet->mergeCells('F5:I5');
                $sheet->setCellValue('F5', '📝 Langkah Penggunaan');
                $this->applySectionTitle($sheet, 'F5:I5', 'Langkah Penggunaan');

                $steps = [
                    ['①', 'Lengkapi IDENTITAS, DATA_SISWA, dan TP_LM_KKTP sesuai kebutuhan.'],
                    ['②', 'Isi KUNCI_JAWABAN lalu pilih: INPUT_JAWABAN_ABCD atau INPUT_SKOR_01.'],
                    ['③', 'Periksa HASIL_T1, OLAH_T2, ANALISIS_T3, DAFTAR_NILAI_T4, REKAP_NILAI_T5.'],
                    ['④', 'Upload file ini via menu Template Excel di SIABSoal. Sistem menghitung ulang.'],
                ];

                $row = 6;
                foreach ($steps as $idx => [$icon, $desc]) {
                    $sheet->setCellValue("F{$row}", $icon);
                    $sheet->setCellValue("G{$row}", $desc);
                    $sheet->mergeCells("G{$row}:I{$row}");
                    $sheet->getStyle("F{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => self::CLR_NAVY]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("G{$row}")->getAlignment()->setWrapText(true);
                    $this->applyBorder($sheet, "F{$row}:I{$row}");

                    if ($idx % 2 === 1) {
                        $sheet->getStyle("F{$row}:I{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::CLR_ZEBRA_EVEN],
                        ]);
                    }

                    $sheet->getRowDimension($row)->setRowHeight(38);
                    $row++;
                }

                // ── Quick Navigation Menu ─────────────────────────────────
                $menuRow = 14;
                $sheet->mergeCells("A{$menuRow}:I{$menuRow}");
                $sheet->setCellValue("A{$menuRow}", '🗂️  Menu Navigasi Cepat Workbook');
                $this->applyHeaderStyle($sheet, "A{$menuRow}:I{$menuRow}");
                $sheet->getRowDimension($menuRow)->setRowHeight(30);

                $menu = [
                    ['IDENTITAS', '📄 Identitas Ujian', self::TAB_IDENTITAS],
                    ['DATA_KELAS', '🏫 Data Kelas', self::TAB_DATA],
                    ['DATA_SISWA', '👥 Data Siswa', self::TAB_DATA],
                    ['KUNCI_JAWABAN', '🔑 Kunci Jawaban', self::TAB_IDENTITAS],
                    ['INPUT_JAWABAN_ABCD', '✏️ Input ABCD', self::TAB_INPUT],
                    ['INPUT_SKOR_01', '🔢 Input Skor 0/1', self::TAB_INPUT],
                    ['HASIL_T1', '📊 Hasil T1', self::TAB_HASIL],
                    ['ANALISIS_T3', '📈 Analisis T3', self::TAB_ANALISIS],
                    ['REKAP_NILAI_T5', '📋 Rekap T5', self::TAB_REKAP],
                ];

                $startRow = $menuRow + 2;
                $colPairs = [['A', 'C'], ['D', 'F'], ['G', 'I']];

                foreach ($menu as $idx => [$target, $caption, $color]) {
                    [$col, $endCol] = $colPairs[$idx % 3];
                    $currentRow = $startRow + intdiv($idx, 3) * 3;
                    $mergeEnd = $currentRow + 1;

                    $sheet->mergeCells("{$col}{$currentRow}:{$endCol}{$mergeEnd}");
                    $sheet->setCellValue("{$col}{$currentRow}", "{$caption}\n{$target}");
                    $sheet->getCell("{$col}{$currentRow}")->getHyperlink()->setUrl("sheet://'{$target}'!A1");

                    $sheet->getStyle("{$col}{$currentRow}:{$endCol}{$mergeEnd}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                        ],
                    ]);
                }

                // ── Column Widths ─────────────────────────────────────────
                foreach (range('A', 'I') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(16);
                }
                $sheet->getColumnDimension('B')->setWidth(24);
                $sheet->getColumnDimension('D')->setWidth(6);
                $sheet->getColumnDimension('E')->setWidth(24);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(20);
            },
        ];
    }
}

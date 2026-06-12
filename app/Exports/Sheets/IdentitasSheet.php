<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IdentitasSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'IDENTITAS';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_IDENTITAS);
                $this->setSheetFooter($sheet, 'IDENTITAS UJIAN');

                // ── Heading ───────────────────────────────────────────
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', '📄  IDENTITAS UJIAN & ADMINISTRASI GURU');
                $this->applyHeaderStyle($sheet, 'A1:E1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // ── Metadata Fields ───────────────────────────────────
                $fields = [
                    'Nama Sekolah' => 'SMP Negeri 2 Tasikmalaya',
                    'Nama Sistem' => 'SIABSoal',
                    'Nama Guru' => $this->ujian?->guru?->nama_guru ?? '-',
                    'Mata Pelajaran' => $this->ujian?->mapel?->nama_mapel ?? '-',
                    'Kelas' => $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-',
                    'Semester' => $this->ujian?->tahunAjaran?->semester ?? '-',
                    'Tahun Ajaran' => $this->ujian?->tahunAjaran?->tahun_ajaran ?? '-',
                    'Jenis Penilaian' => $this->ujian?->jenis_penilaian_label ?? '-',
                    'Tanggal Penilaian' => $this->ujian?->tanggal_ujian?->format('d/m/Y') ?? '-',
                    'Jumlah Soal' => $this->ujian?->jumlah_soal ?? 0,
                    'KKTP/KKM' => $this->ujian?->kktp_value ?? 75,
                    'Lingkup Materi' => $this->ujian?->lingkup_materi ?? '-',
                    'Tujuan Pembelajaran' => $this->ujian?->tujuan_pembelajaran ?? '-',
                    'Keterangan' => '',
                ];

                $row = 3;
                $idx = 0;
                foreach ($fields as $label => $value) {
                    $sheet->setCellValue("A{$row}", $label);
                    $sheet->setCellValue("B{$row}", $value);
                    $sheet->mergeCells("B{$row}:E{$row}");

                    $this->applyMetaLabel($sheet, "A{$row}");
                    $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);
                    $this->applyInputStyle($sheet, "B{$row}:E{$row}");

                    // Zebra
                    if ($idx % 2 === 1) {
                        $sheet->getStyle("A{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::CLR_ZEBRA_EVEN],
                        ]);
                    }

                    if ($label === 'Jenis Penilaian') {
                        $this->addDropdown($sheet, "B{$row}", [
                            'Ulangan Harian', 'Penilaian Harian', 'STS', 'SAS',
                            'PAS', 'PAT', 'Asesmen Sumatif', 'Latihan',
                        ]);
                    }

                    if ($label === 'KKTP/KKM') {
                        $this->addWholeNumberValidation($sheet, "B{$row}", 0, 100);
                    }

                    $this->applyBorder($sheet, "A{$row}:E{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(
                        in_array($label, ['Tujuan Pembelajaran', 'Keterangan'], true) ? 45 : 24
                    );
                    $row++;
                    $idx++;
                }

                // ── Spacing ───────────────────────────────────────────
                $row += 2;

                // ── TP Table ──────────────────────────────────────────
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", '📝  TABEL TUJUAN PEMBELAJARAN (TP)');
                $this->applySectionTitle($sheet, "A{$row}:E{$row}", 'TP');
                $sheet->getRowDimension($row)->setRowHeight(28);
                $row++;

                $sheet->setCellValue("A{$row}", 'Kode TP');
                $sheet->setCellValue("B{$row}", 'Tujuan Pembelajaran');
                $sheet->setCellValue("C{$row}", 'Lingkup Materi');
                $sheet->setCellValue("D{$row}", 'KKTP');
                $sheet->setCellValue("E{$row}", 'Nomor Soal Terkait');
                $this->applyHeaderStyle($sheet, "A{$row}:E{$row}");
                $sheet->getRowDimension($row)->setRowHeight(25);
                $row++;

                $tpData = [
                    ['TP 1', $this->ujian?->tujuan_pembelajaran ?: 'Menjelaskan konsep materi yang diujikan', $this->ujian?->lingkup_materi ?: 'Lingkup Materi 1', $this->ujian?->kktp_value ?? 75, '1-5'],
                    ['TP 2', '', '', $this->ujian?->kktp_value ?? 75, '6-10'],
                    ['TP 3', '', '', $this->ujian?->kktp_value ?? 75, '11-15'],
                    ['TP 4', '', '', $this->ujian?->kktp_value ?? 75, '16-20'],
                ];

                foreach ($tpData as $tpIdx => $tp) {
                    $sheet->setCellValue("A{$row}", $tp[0]);
                    $sheet->setCellValue("B{$row}", $tp[1]);
                    $sheet->setCellValue("C{$row}", $tp[2]);
                    $sheet->setCellValue("D{$row}", $tp[3]);
                    $sheet->setCellValue("E{$row}", $tp[4]);

                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);

                    // Zebra
                    if ($tpIdx % 2 === 1) {
                        $sheet->getStyle("A{$row}:E{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::CLR_ZEBRA_EVEN],
                        ]);
                    }

                    $this->applyBorder($sheet, "A{$row}:E{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(40);
                    $row++;
                }

                // ── Column Widths ─────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(45);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(10);
                $sheet->getColumnDimension('E')->setWidth(20);

                // ── Print Setup ───────────────────────────────────────
                $this->setPrintArea($sheet, "A1:E{$row}");
            }
        ];
    }
}

<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DaftarHadirSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'DAFTAR_HADIR';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(true);
                $this->setTabColor($sheet, self::TAB_DATA);
                $this->setSheetFooter($sheet, 'DAFTAR HADIR');

                // Header
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', '📋  DAFTAR HADIR SISWA');
                $this->applyHeaderStyle($sheet, 'A1:H1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                // Ujian Metadata short summary
                $sheet->setCellValue('A3', 'Mata Pelajaran:');
                $sheet->setCellValue('B3', $this->ujian?->mapel?->nama_mapel ?? '-');
                $sheet->setCellValue('E3', 'Kelas:');
                $sheet->setCellValue('F3', $this->ujian?->kelas?->pluck('nama_kelas')->join(', ') ?: '-');

                $sheet->setCellValue('A4', 'Nama Guru:');
                $sheet->setCellValue('B4', $this->ujian?->guru?->nama_guru ?? '-');
                $sheet->setCellValue('E4', 'Jenis Penilaian:');
                $sheet->setCellValue('F4', $this->ujian?->jenis_penilaian_label ?? '-');

                $this->applyMetaLabel($sheet, 'A3');
                $this->applyMetaLabel($sheet, 'A4');
                $this->applyMetaLabel($sheet, 'E3');
                $this->applyMetaLabel($sheet, 'E4');

                // Table Headers
                $headers = ['No', 'NIS', 'NISN', 'Nama Murid', 'L/P', 'Kelas', 'Status Kehadiran', 'Catatan'];
                foreach ($headers as $colIdx => $header) {
                    $cell = Coordinate::stringFromColumnIndex($colIdx + 1) . '6';
                    $sheet->setCellValue($cell, $header);
                }
                $this->applySubHeaderStyle($sheet, 'A6:H6');
                $sheet->getRowDimension(6)->setRowHeight(25);

                $pesertaList = $this->ujian
                    ? $this->ujian->pesertaUjian()
                        ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama_siswa')
                        ->select('peserta_ujian.*')
                        ->get()
                    : collect();

                $row = 7;
                $idx = 1;
                foreach ($pesertaList as $p) {
                    $sheet->setCellValue("A{$row}", $idx);
                    $sheet->setCellValue("B{$row}", $p->siswa->nis ?? '');
                    $sheet->setCellValue("C{$row}", $p->siswa->nisn ?? '');
                    $sheet->setCellValue("D{$row}", $p->siswa->nama_siswa ?? '');
                    $sheet->setCellValue("E{$row}", $p->siswa->jenis_kelamin ?? '');
                    $sheet->setCellValue("F{$row}", $p->kelas->nama_kelas ?? $this->ujian?->kelas?->first()?->nama_kelas ?? '');
                    $sheet->setCellValue("G{$row}", $p->status_kehadiran ?? 'hadir');
                    $sheet->setCellValue("H{$row}", '');

                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Border
                    $this->applyBorder($sheet, "A{$row}:H{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(24);

                    // Dropdown for Status Kehadiran
                    $this->addDropdown($sheet, "G{$row}", ['hadir', 'tidak_hadir', 'izin', 'sakit', 'alfa']);

                    $row++;
                    $idx++;
                }

                // If empty list, add placeholder rows
                if ($pesertaList->isEmpty()) {
                    for ($i = 0; $i < 5; $i++) {
                        $sheet->setCellValue("A{$row}", $idx);
                        $this->applyBorder($sheet, "A{$row}:H{$row}");
                        $sheet->getRowDimension($row)->setRowHeight(24);
                        $this->addDropdown($sheet, "G{$row}", ['hadir', 'tidak_hadir', 'izin', 'sakit', 'alfa']);
                        $row++;
                        $idx++;
                    }

                // Zebra striping
                $lastRow = $row - 1;
                if ($lastRow >= 7) {
                    $this->applyAlternatingRows($sheet, 7, $lastRow, 'A', 'H');
                }
                }

                // Column dimensions
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(35);
                $sheet->getColumnDimension('E')->setWidth(8);
                $sheet->getColumnDimension('F')->setWidth(12);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(25);

                // Print Setup
                $this->setPrintArea($sheet, "A1:H" . max($row, 12));
            }
        ];
    }
}

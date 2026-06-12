<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DataSiswaSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'DATA_SISWA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_DATA);
                $this->setSheetFooter($sheet, 'DATA SISWA');

                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', '👥  DATA SISWA');
                $this->applyHeaderStyle($sheet, 'A1:G1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                $headers = ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'status_siswa'];
                foreach ($headers as $idx => $header) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 1) . '4', $header);
                }
                $this->applySubHeaderStyle($sheet, 'A4:G4');

                $pesertaList = $this->ujian
                    ? $this->ujian->pesertaUjian()
                        ->join('siswa', 'peserta_ujian.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama_siswa')
                        ->select('peserta_ujian.*')
                        ->get()
                    : collect();

                $row = 5;
                $no = 1;
                foreach ($pesertaList as $peserta) {
                    $sheet->setCellValue("A{$row}", $no++);
                    $sheet->setCellValue("B{$row}", $peserta->siswa->nis ?? '');
                    $sheet->setCellValue("C{$row}", $peserta->siswa->nisn ?? '');
                    $sheet->setCellValue("D{$row}", $peserta->siswa->nama_siswa ?? '');
                    $sheet->setCellValue("E{$row}", $peserta->siswa->jenis_kelamin ?? '');
                    $sheet->setCellValue("F{$row}", $peserta->kelas->nama_kelas ?? $this->ujian?->kelas?->first()?->nama_kelas ?? '');
                    $sheet->setCellValue("G{$row}", $peserta->siswa->status ?? 'aktif');
                    $this->addDropdown($sheet, "E{$row}", ['L', 'P']);
                    $this->addDropdown($sheet, "G{$row}", ['aktif', 'nonaktif', 'lulus']);
                    $this->applyInputStyle($sheet, "A{$row}:G{$row}");
                    $row++;
                }

                if ($pesertaList->isEmpty()) {
                    for ($i = 1; $i <= 40; $i++, $row++) {
                        $sheet->setCellValue("A{$row}", $i);
                        $sheet->setCellValue("F{$row}", $this->ujian?->kelas?->first()?->nama_kelas ?? '');
                        $sheet->setCellValue("G{$row}", 'aktif');
                        $this->addDropdown($sheet, "E{$row}", ['L', 'P']);
                        $this->addDropdown($sheet, "G{$row}", ['aktif', 'nonaktif', 'lulus']);
                        $this->applyInputStyle($sheet, "A{$row}:G{$row}");
                    }
                }

                $sheet->freezePane('A5');
                $sheet->getStyle('A5:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E5:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(16);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(34);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);

                // Print Setup
                $this->setPrintArea($sheet, "A1:G" . max($row - 1, 12));
            },
        ];
    }
}

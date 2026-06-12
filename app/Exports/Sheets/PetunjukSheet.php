<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PetunjukSheet extends BaseSheet
{
    public function __construct(private readonly ?string $type)
    {
        $this->title = 'PETUNJUK';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_PETUNJUK);
                $this->setSheetFooter($sheet, 'PETUNJUK PENGISIAN');

                // ── Title Banner ──────────────────────────────────────
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', '📖  PETUNJUK PENGISIAN TEMPLATE PENILAIAN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_NAVY]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(42);

                // ── Subtitle ──────────────────────────────────────────
                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'SIABSoal — SMP Negeri 2 Tasikmalaya');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => self::CLR_TEXT_SECONDARY]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_BLUE_SOFT]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // ── Step Headers ──────────────────────────────────────
                $headers = ['No', 'Langkah Pengisian / Aturan', 'Keterangan Detail', '', '', '', ''];
                for ($col = 0; $col < count($headers); $col++) {
                    $cell = Coordinate::stringFromColumnIndex($col + 1) . '4';
                    if ($headers[$col] !== '') {
                        $sheet->setCellValue($cell, $headers[$col]);
                    }
                }
                $sheet->mergeCells('B4:C4');
                $sheet->mergeCells('D4:G4');
                $this->applyHeaderStyle($sheet, 'A4:G4');
                $sheet->getRowDimension(4)->setRowHeight(28);

                // ── Steps ─────────────────────────────────────────────
                $steps = [
                    ['①', 'Isi IDENTITAS Terlebih Dahulu', 'Buka sheet IDENTITAS dan sesuaikan data Mata Pelajaran, Kelas, Guru, KKTP/KKM, Lingkup Materi, dan Tujuan Pembelajaran.', self::CLR_BLUE_SOFT],
                    ['②', 'Cek DAFTAR_HADIR', 'Buka sheet DAFTAR_HADIR untuk memastikan daftar nama siswa sudah sesuai. Pilih status kehadiran siswa (hadir, tidak_hadir, izin, sakit, alfa) menggunakan dropdown.', self::CLR_ZEBRA_EVEN],
                    ['③', 'Isi KUNCI_JAWABAN', 'Buka sheet KUNCI_JAWABAN. Isi kunci jawaban untuk setiap nomor soal menggunakan dropdown (A/B/C/D/E) dan tentukan bobot masing-masing soal.', self::CLR_BLUE_SOFT],
                    ['④', 'Isi Jawaban Siswa', "Pilih salah satu sheet input yang sesuai:\n• INPUT_JAWABAN_ABCD: Jika ingin memasukkan opsi jawaban siswa (A/B/C/D/E).\n• INPUT_SKOR_01: Jika sudah memeriksa jawaban dan langsung memasukkan skor biner (1=benar, 0=salah).", self::CLR_ZEBRA_EVEN],
                    ['⑤', 'Simpan dan Upload', 'Setelah semua data terisi, simpan file Excel ini. Pastikan sheet DATA_IMPORT_SYSTEM atau DATA_INPUT tidak dihapus karena sheet tersebut dibaca otomatis oleh sistem SIABSoal saat diupload.', self::CLR_GREEN_BG],
                ];

                $row = 5;
                foreach ($steps as [$icon, $title, $desc, $bgColor]) {
                    $sheet->setCellValue("A{$row}", $icon);
                    $sheet->setCellValue("B{$row}", $title);
                    $sheet->setCellValue("D{$row}", $desc);

                    $sheet->mergeCells("B{$row}:C{$row}");
                    $sheet->mergeCells("D{$row}:G{$row}");

                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::CLR_NAVY]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::CLR_NAVY]],
                    ]);
                    $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

                    // Row background
                    $sheet->getStyle("A{$row}:G{$row}")->getFill()->applyFromArray([
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor],
                    ]);

                    $this->applyBorder($sheet, "A{$row}:G{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(50);
                    $row++;
                }

                // ── Spacing ───────────────────────────────────────────
                $row++;

                // ── Aturan & Validasi ─────────────────────────────────
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->setCellValue("A{$row}", '⚠️  ATURAN DAN VALIDASI IMPORT SYSTEM');
                $this->applySectionTitle($sheet, "A{$row}:G{$row}", 'ATURAN');
                $sheet->getRowDimension($row)->setRowHeight(28);
                $row++;

                $rules = [
                    ['🚫 Jangan Ubah Struktur Sheet', 'Dilarang menghapus atau mengubah nama sheet terutama DATA_IMPORT_SYSTEM dan DATA_INPUT karena system membaca data dari sana.', self::CLR_RED_BG],
                    ['✅ Format Jawaban ABCD', 'Format jawaban pilihan ganda hanya boleh diisi opsi A, B, C, D, atau E (huruf kapital). Kolom kosong dianggap salah/tidak menjawab.', self::CLR_ZEBRA_EVEN],
                    ['✅ Format Skor Biner 0/1', 'Format skor hanya boleh diisi angka 1 (benar) atau 0 (salah). Kolom kosong akan otomatis dihitung sebagai 0.', self::CLR_BLUE_SOFT],
                    ['📋 Status Kehadiran', 'Wajib menggunakan status kehadiran: hadir, tidak_hadir, izin, sakit, atau alfa.', self::CLR_ZEBRA_EVEN],
                    ['🔗 Kesesuaian NIS', 'Nomor Induk Siswa (NIS) pada file Excel wajib sesuai dengan NIS yang terdaftar pada database sistem SIABSoal.', self::CLR_YELLOW_BG],
                ];

                foreach ($rules as [$title, $desc, $bgColor]) {
                    $sheet->setCellValue("A{$row}", '•');
                    $sheet->setCellValue("B{$row}", $title);
                    $sheet->setCellValue("D{$row}", $desc);

                    $sheet->mergeCells("B{$row}:C{$row}");
                    $sheet->mergeCells("D{$row}:G{$row}");

                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

                    $sheet->getStyle("A{$row}:G{$row}")->getFill()->applyFromArray([
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor],
                    ]);

                    $this->applyBorder($sheet, "A{$row}:G{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(38);
                    $row++;
                }

                // ── Spacing ───────────────────────────────────────────
                $row++;

                // ── Color Legend ───────────────────────────────────────
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->setCellValue("A{$row}", '🎨  KETERANGAN WARNA ADMINISTRASI');
                $this->applySectionTitle($sheet, "A{$row}:G{$row}", 'WARNA');
                $sheet->getRowDimension($row)->setRowHeight(28);
                $row++;

                $colors = [
                    ['Hijau Muda', '✅ Tuntas / Benar / Kualitas Baik', self::CLR_GREEN_BG, self::CLR_GREEN_TEXT],
                    ['Kuning Muda', '⚡ Perlu Perhatian / Perbaikan / Kategori Sedang', self::CLR_YELLOW_BG, self::CLR_YELLOW_TEXT],
                    ['Merah Muda', '❌ Belum Tuntas / Salah / Kualitas Rendah', self::CLR_RED_BG, self::CLR_RED_TEXT],
                    ['Abu-abu', '⬜ Tidak Hadir / Kosong', self::CLR_GRAY_BG, self::CLR_GRAY_TEXT],
                ];

                foreach ($colors as [$name, $desc, $colorRGB, $textRGB]) {
                    $sheet->setCellValue("A{$row}", '■');
                    $sheet->setCellValue("B{$row}", $name);
                    $sheet->setCellValue("D{$row}", $desc);

                    $sheet->mergeCells("B{$row}:C{$row}");
                    $sheet->mergeCells("D{$row}:G{$row}");

                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => $textRGB], 'size' => 16],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $textRGB]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorRGB]],
                    ]);

                    $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

                    $this->applyBorder($sheet, "A{$row}:G{$row}");
                    $sheet->getRowDimension($row)->setRowHeight(32);
                    $row++;
                }

                // ── Column widths ─────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(5);
                $sheet->getColumnDimension('D')->setWidth(60);

                // ── Print setup ───────────────────────────────────────
                $this->setPrintArea($sheet, "A1:G{$row}");
            },
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Exports\AssessmentTemplateExport;
use App\Exports\Sheets\AnalisisT3Sheet;
use App\Models\Mapel;
use App\Models\Ujian;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AnalisisT3SheetFeatureTest extends TestCase
{
    public function test_complete_template_export_contains_the_sixteen_official_sheets(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'siabsoal-complete-');
        file_put_contents(
            $path,
            Excel::raw(new AssessmentTemplateExport('lengkap'), ExcelFormat::XLSX)
        );

        try {
            $sheetNames = IOFactory::load($path)->getSheetNames();

            $this->assertSame([
                'BERANDA',
                'IDENTITAS',
                'DATA_KELAS',
                'DATA_SISWA',
                'TP_LM_KKTP',
                'DAFTAR_HADIR',
                'KUNCI_JAWABAN',
                'INPUT_JAWABAN_ABCD',
                'INPUT_SKOR_01',
                'HASIL_T1',
                'OLAH_T2',
                'ANALISIS_T3',
                'DAFTAR_NILAI_T4',
                'REKAP_NILAI_T5',
                'DATA_IMPORT_SYSTEM',
                'REFERENSI',
            ], $sheetNames);
        } finally {
            @unlink($path);
        }
    }

    public function test_unprocessed_analysis_is_exported_as_belum_dianalisis(): void
    {
        $ujian = new Ujian([
            'nama_ujian' => 'Ujian Belum Dianalisis',
            'jumlah_soal' => 1,
            'kktp' => 75,
            'kkm' => 75,
            'status' => 'kunci_lengkap',
        ]);
        $ujian->setRelation('mapel', new Mapel(['nama_mapel' => 'Matematika']));
        $ujian->setRelation('kelas', collect());
        $ujian->setRelation('analisisButir', collect());

        $export = new class($ujian) implements WithMultipleSheets {
            public function __construct(private readonly Ujian $ujian)
            {
            }

            public function sheets(): array
            {
                return [new AnalisisT3Sheet($this->ujian)];
            }
        };

        $path = tempnam(sys_get_temp_dir(), 'siabsoal-t3-');
        file_put_contents($path, Excel::raw($export, ExcelFormat::XLSX));

        try {
            $sheet = IOFactory::load($path)->getSheetByName('ANALISIS_T3');

            $this->assertSame('-', $sheet->getCell('G7')->getValue());
            $this->assertSame('Belum Dianalisis', $sheet->getCell('H7')->getValue());
            $this->assertSame('-', $sheet->getCell('I7')->getValue());
            $this->assertSame('Belum Dianalisis', $sheet->getCell('J7')->getValue());
            $this->assertSame('Belum Dianalisis', $sheet->getCell('K7')->getValue());
        } finally {
            @unlink($path);
        }
    }
}

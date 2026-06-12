<?php

namespace App\Exports;

use App\Models\Ujian;
use App\Exports\Sheets\BerandaSheet;
use App\Exports\Sheets\PetunjukSheet;
use App\Exports\Sheets\IdentitasSheet;
use App\Exports\Sheets\DataKelasSheet;
use App\Exports\Sheets\DataSiswaSheet;
use App\Exports\Sheets\TpLmKktpSheet;
use App\Exports\Sheets\DaftarHadirSheet;
use App\Exports\Sheets\KunciJawabanSheet;
use App\Exports\Sheets\InputJawabanABCDSheet;
use App\Exports\Sheets\InputSkor01Sheet;
use App\Exports\Sheets\HasilT1Sheet;
use App\Exports\Sheets\OlahT2Sheet;
use App\Exports\Sheets\DaftarNilaiSheet;
use App\Exports\Sheets\AnalisisT3Sheet;
use App\Exports\Sheets\RekapNilaiSheet;
use App\Exports\Sheets\DataImportSystemSheet;
use App\Exports\Sheets\InputSiswaSheet;
use App\Exports\Sheets\ReferensiSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssessmentTemplateExport implements WithMultipleSheets
{
    public const TYPES = [
        'data-siswa' => 'Template Data Siswa',
        'kunci-jawaban' => 'Template Kunci Jawaban',
        'jawaban-abcd' => 'Template Jawaban A/B/C/D/E',
        'skor-01' => 'Template Skor 0/1',
        'daftar-nilai' => 'Template Daftar Nilai',
        'rekap-nilai' => 'Template Rekap Nilai',
        'lengkap' => 'Template Excel Lengkap',
    ];

    public static function label(string $type): string
    {
        return self::TYPES[$type] ?? 'Template Excel';
    }

    public function __construct(
        private readonly string $type,
        private readonly ?Ujian $ujian = null
    ) {
        if ($this->ujian) {
            $this->ujian->loadMissing([
                'guru',
                'mapel',
                'kelas',
                'tahunAjaran',
                'soal',
                'pesertaUjian.siswa',
                'pesertaUjian.kelas',
                'pesertaUjian.jawabanSiswa.soal',
                'analisisButir.soal',
            ]);
        }
    }

    public function sheets(): array
    {
        if ($this->type === 'lengkap' || $this->type === 'template-lengkap') {
            return [
                new BerandaSheet($this->ujian),
                new IdentitasSheet($this->ujian),
                new DataKelasSheet($this->ujian),
                new DataSiswaSheet($this->ujian),
                new TpLmKktpSheet($this->ujian),
                new DaftarHadirSheet($this->ujian),
                new KunciJawabanSheet($this->ujian),
                new InputJawabanABCDSheet($this->ujian),
                new InputSkor01Sheet($this->ujian),
                new HasilT1Sheet($this->ujian),
                new OlahT2Sheet($this->ujian),
                new AnalisisT3Sheet($this->ujian),
                new DaftarNilaiSheet($this->ujian, 'DAFTAR_NILAI_T4'),
                new RekapNilaiSheet($this->ujian, 'REKAP_NILAI_T5', 'DAFTAR_NILAI_T4'),
                new DataImportSystemSheet('skor-01', $this->ujian, 'DATA_IMPORT_SYSTEM'),
                new ReferensiSheet($this->ujian),
            ];
        }

        if ($this->type === 'data-siswa' || $this->type === 'data_siswa') {
            return [
                new PetunjukSheet($this->type),
                new InputSiswaSheet(),
                new DataImportSystemSheet($this->type, $this->ujian, 'DATA_INPUT'),
                new DataImportSystemSheet($this->type, $this->ujian, 'DATA_IMPORT_SYSTEM'),
            ];
        }

        return [
            new PetunjukSheet($this->type),
            new IdentitasSheet($this->ujian),
            new DaftarHadirSheet($this->ujian),
            new KunciJawabanSheet($this->ujian),
            new InputJawabanABCDSheet($this->ujian),
            new InputSkor01Sheet($this->ujian),
            new DaftarNilaiSheet($this->ujian),
            new AnalisisT3Sheet($this->ujian),
            new RekapNilaiSheet($this->ujian),
            new DataImportSystemSheet($this->type, $this->ujian, 'DATA_INPUT'),
            new DataImportSystemSheet($this->type, $this->ujian, 'DATA_IMPORT_SYSTEM'),
        ];
    }
}

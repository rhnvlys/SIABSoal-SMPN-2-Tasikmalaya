<?php

namespace Tests\Unit;

use App\Models\AnalisisButir;
use PHPUnit\Framework\TestCase;

class AnalisisButirStatusTest extends TestCase
{
    public function test_incomplete_analysis_is_not_treated_as_an_analysis_result(): void
    {
        $analysis = new AnalisisButir([
            'ba' => null,
            'bb' => null,
            'ja' => null,
            'jb' => null,
            'dp' => null,
            'tk' => null,
            'kategori_dp' => null,
            'kategori_tk' => null,
            'keputusan' => null,
        ]);

        $this->assertFalse($analysis->hasAnalysisResult());
    }

    public function test_completed_analysis_accepts_zero_as_a_real_result(): void
    {
        $analysis = new AnalisisButir([
            'ba' => 0,
            'bb' => 0,
            'ja' => 1,
            'jb' => 1,
            'dp' => 0,
            'tk' => 0,
            'kategori_dp' => 'Jelek',
            'kategori_tk' => 'Sukar',
            'keputusan' => 'Buang',
        ]);

        $this->assertTrue($analysis->hasAnalysisResult());
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;

class LandingController extends Controller
{
    public function __invoke()
    {
        return view('landing', [
            'pengaturan' => PengaturanSekolah::getSettings(),
        ]);
    }
}

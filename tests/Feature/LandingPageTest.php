<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_introduces_siabsoal_and_login_action(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SIABSoal SMPN 2 Tasikmalaya')
            ->assertSee('Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya')
            ->assertSee('Login');
    }
}

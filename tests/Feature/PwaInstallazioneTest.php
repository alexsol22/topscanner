<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaInstallazioneTest extends TestCase
{
    public function test_la_pagina_di_accesso_propone_linstallazione_sul_telefono(): void
    {
        $risposta = $this->get('/login');

        $risposta->assertOk();
        $risposta->assertSee('rel="manifest"', false);
        $risposta->assertSee(route('pwa.manifest'), false);
        $risposta->assertSee('apple-mobile-web-app-capable', false);
        $risposta->assertSee('apple-touch-icon', false);
        $risposta->assertSee('serviceWorker.register', false);
    }

    public function test_il_manifest_descrive_un_app_a_schermo_intero(): void
    {
        $risposta = $this->get(route('pwa.manifest'));

        $risposta->assertOk();
        $risposta->assertHeader('Content-Type', 'application/manifest+json');
        $risposta->assertJsonPath('name', 'Omnia Control');
        $risposta->assertJsonPath('short_name', 'Omnia');
        $risposta->assertJsonPath('display', 'standalone');
        $risposta->assertJsonPath('start_url', '/');
        $risposta->assertJsonPath('icons.0.src', '/brand/icon-192.png');
        $risposta->assertJsonPath('icons.1.src', '/brand/icon-512.png');
        $risposta->assertJsonPath('icons.2.purpose', 'maskable');

        foreach ([
            'brand/icon-192.png' => 192,
            'brand/icon-512.png' => 512,
            'brand/apple-touch-icon.png' => 180,
            'brand/icon-maskable-512.png' => 512,
        ] as $file => $lato) {
            $percorso = public_path($file);
            $this->assertFileExists($percorso);
            $misure = getimagesize($percorso);
            $this->assertSame($lato, $misure[0]);
            $this->assertSame($lato, $misure[1]);
        }

        $this->assertStringContainsString("addEventListener('fetch'", (string) file_get_contents(public_path('sw.js')));
    }
}

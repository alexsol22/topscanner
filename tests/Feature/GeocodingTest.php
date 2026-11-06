<?php

namespace Tests\Feature;

use App\Services\Geocoding;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingTest extends TestCase
{
    public function test_senza_chiave_non_chiama_il_servizio(): void
    {
        config(['services.google_maps.key' => null]);
        Http::fake();

        $this->assertNull((new Geocoding)->coordinate('Via Roma 1 10100 Torino'));
        Http::assertNothingSent();
    }

    public function test_restituisce_le_coordinate_del_primo_risultato(): void
    {
        config(['services.google_maps.key' => 'chiave']);
        Http::fake(['maps.googleapis.com/*' => Http::response(['results' => [['geometry' => ['location' => ['lat' => 45.07, 'lng' => 7.68]]]]])]);

        $this->assertSame(['lat' => 45.07, 'lng' => 7.68], (new Geocoding)->coordinate('Via Roma 1 10100 Torino'));
        Http::assertSent(fn ($r) => $r['address'] === 'Via Roma 1 10100 Torino' && $r['key'] === 'chiave');
    }

    public function test_indirizzo_non_risolto_restituisce_null(): void
    {
        config(['services.google_maps.key' => 'chiave']);
        Http::fake(['maps.googleapis.com/*' => Http::response(['results' => []])]);

        $this->assertNull((new Geocoding)->coordinate('Indirizzo inesistente'));
    }
}

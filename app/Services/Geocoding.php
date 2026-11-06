<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Coordinate da indirizzo: porting di MyUtils::getLatAndLong (Google Geocoding). */
class Geocoding
{
    /** @return array{lat: float, lng: float}|null null se manca la chiave o l'indirizzo non è risolto */
    public function coordinate(string $indirizzo): ?array
    {
        $chiave = config('services.google_maps.key');

        if (! $chiave || trim($indirizzo) === '') {
            return null;
        }

        try {
            $posizione = Http::timeout(10)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', ['address' => $indirizzo, 'key' => $chiave])
                ->json('results.0.geometry.location');
        } catch (Throwable $e) {
            Log::warning("Geocoding fallito per «{$indirizzo}»: {$e->getMessage()}");

            return null;
        }

        $lat = (float) ($posizione['lat'] ?? 0);
        $lng = (float) ($posizione['lng'] ?? 0);

        return $lat > 0 && $lng > 0 ? ['lat' => $lat, 'lng' => $lng] : null;
    }
}

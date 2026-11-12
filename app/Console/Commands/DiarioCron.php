<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;

trait DiarioCron
{
    /** @param  array<string, mixed>  $contesto */
    protected function diario(string $messaggio, array $contesto = []): void
    {
        $this->registraDiario('info', $messaggio, $contesto);
    }

    /** @param  array<string, mixed>  $contesto */
    protected function diarioAttenzione(string $messaggio, array $contesto = []): void
    {
        $this->registraDiario('warning', $messaggio, $contesto);
    }

    /** @param  array<string, mixed>  $contesto */
    private function registraDiario(string $livello, string $messaggio, array $contesto): void
    {
        if ($livello === 'warning') {
            $this->warn($messaggio);
        } else {
            $this->info($messaggio);
        }

        $contesto = ['comando' => (string) $this->getName()] + $contesto;

        if ($livello === 'warning') {
            Log::channel('cron')->warning($messaggio, $contesto);

            return;
        }

        Log::channel('cron')->info($messaggio, $contesto);
    }
}

<?php

namespace App\Listeners;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;

class RegistraEsecuzioneCron
{
    public function handle(ScheduledTaskStarting|ScheduledTaskFinished|ScheduledTaskFailed|ScheduledTaskSkipped $evento): void
    {
        $contesto = $this->contesto($evento->task);

        if ($evento instanceof ScheduledTaskStarting) {
            Log::channel('cron')->info('Cron avviato', $contesto);

            return;
        }

        if ($evento instanceof ScheduledTaskSkipped) {
            Log::channel('cron')->warning('Cron saltato', $contesto + [
                'motivo' => $evento->reason?->value ?? 'sconosciuto',
            ]);

            return;
        }

        if ($evento instanceof ScheduledTaskFinished) {
            if ($evento->task->skippedBecauseOverlapping) {
                Log::channel('cron')->warning('Cron saltato: esecuzione precedente ancora in corso', $contesto);

                return;
            }

            if ($evento->task->exitCode !== 0) {
                return;
            }

            Log::channel('cron')->info('Cron completato', $contesto + [
                'secondi' => $evento->runtime,
                'uscita' => $evento->task->exitCode,
            ]);

            return;
        }

        if ($evento->task->skippedBecauseOverlapping) {
            return;
        }

        Log::channel('cron')->error('Cron fallito', $contesto + [
            'secondi' => $evento->runtime,
            'uscita' => $evento->task->exitCode,
            'errore' => $evento->exception->getMessage(),
        ]);
    }

    /** @return array{comando: string, riga: string} */
    private function contesto(Event $task): array
    {
        $riga = trim((string) ($task->command ?? ''));

        if ($riga === '') {
            $riga = $task->getSummaryForDisplay();
        }

        $comando = $riga;

        if (preg_match('/artisan(?:\.php)?[\'"]?\s+[\'"]?([^\s\'"]+)/', $riga, $trovato) === 1) {
            $comando = $trovato[1];
        }

        return ['comando' => $comando, 'riga' => $riga];
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Legacy\Messaggio;
use App\Services\Comunicazioni;
use Illuminate\Console\Command;

class InviaAvvisoTurni extends Command
{
    protected $signature = 'notifiche:avviso-turni';

    protected $description = 'Invia agli operatori l\'email di promemoria per i turni in partenza (modello messaggio 1)';

    public function handle(Comunicazioni $comunicazioni): int
    {
        $config = Messaggio::find(1);

        if (! $config) {
            return self::SUCCESS;
        }

        $giorno = today()->addDays((int) $config->giorni);
        $ora = now()->startOfHour()->addDays((int) $config->giorni)->addHours((int) $config->ore)->format('H:i');

        $turni = $comunicazioni->turni()
            ->whereDate('t.data_inizio', $giorno)
            ->where('t.ora_inizio', $ora)
            ->get();

        $inviate = 0;

        foreach ($turni as $turno) {
            $dati = (array) $turno + ['giorno' => $turno->inizio, 'orario' => $turno->ora_inizio];

            $comunicazioni->inviaSms($config, $dati, $turno->cellulare, (int) $turno->id_operatore);

            if (! $config->invia_email || ! $turno->email) {
                continue;
            }

            $inviate += $comunicazioni->invia(
                [$turno->email => trim("{$turno->cognome} {$turno->nome}")],
                (string) $config->oggetto,
                $comunicazioni->testo($config->modello_email, $dati),
                (int) $turno->id_operatore,
            );
        }

        $this->info("Avvisi turno inviati: {$inviate}");

        return self::SUCCESS;
    }
}

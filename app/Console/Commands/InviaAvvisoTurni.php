<?php

namespace App\Console\Commands;

use App\Models\Legacy\Messaggio;
use App\Services\Comunicazioni;
use App\Services\ProvaNotifiche;
use Illuminate\Console\Command;

class InviaAvvisoTurni extends Command
{
    use DiarioCron;

    protected $signature = 'notifiche:avviso-turni {--prova-sicura : Limita la prova all’operatore e al cliente autorizzati: mail solo a lui, senza SMS né modifiche}';

    protected $description = 'Invia agli operatori l\'email di promemoria per i turni in partenza (modello messaggio 1)';

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $this->diario('Avvio avviso turni', [
            'prova_sicura' => (bool) $this->option('prova-sicura'),
        ]);

        $contesto = $prova->contesto($comunicazioni, (bool) $this->option('prova-sicura'));
        $config = Messaggio::find(1);

        if (! $config) {
            $this->diarioAttenzione('Modello avviso turni assente');

            return self::SUCCESS;
        }

        $giorno = today()->addDays((int) $config->giorni);
        $ora = now()->startOfHour()->addDays((int) $config->giorni)->addHours((int) $config->ore)->format('H:i');

        $turni = $comunicazioni->turni()
            ->when($contesto !== null, fn ($query) => $query->where('t.id_operatore', $contesto['operatore'])->where('t.id_cliente', $contesto['cliente']))
            ->whereDate('t.data_inizio', $giorno)
            ->where('t.ora_inizio', $ora)
            ->get();

        $inviate = 0;

        foreach ($turni as $turno) {
            $dati = (array) $turno + ['giorno' => $turno->inizio, 'orario' => $turno->ora_inizio];
            $sms = $comunicazioni->inviaSms($config, $dati, $turno->cellulare, (int) $turno->id_operatore);
            $emailInviate = 0;
            $motivoEmail = null;

            if (! $config->invia_email) {
                $motivoEmail = 'email disattivate sul modello';
            } elseif (! $turno->email) {
                $motivoEmail = 'operatore senza email';
            } else {
                $emailInviate = $comunicazioni->invia(
                    [$turno->email => trim("{$turno->cognome} {$turno->nome}")],
                    (string) $config->oggetto,
                    $comunicazioni->testo($config->modello_email, $dati),
                    (int) $turno->id_operatore,
                );
                $inviate += $emailInviate;
            }

            $this->diario('Avviso turno elaborato', [
                'turno' => (int) $turno->id,
                'operatore' => (int) $turno->id_operatore,
                'sede' => (string) $turno->descrizione,
                'giorno' => (string) $turno->inizio,
                'orario' => (string) $turno->ora_inizio,
                'sms_inviati' => $sms['inviati'],
                'sms_falliti' => $sms['falliti'],
                'motivo_sms' => $sms['motivo'],
                'email_inviate' => $emailInviate,
                'email' => (string) $turno->email,
                'motivo_email' => $motivoEmail,
            ]);
        }

        $this->diario('Avvisi turno completati', [
            'prova' => $contesto !== null,
            'operatore' => $contesto['operatore'] ?? null,
            'cliente' => $contesto['cliente'] ?? null,
            'giorno' => $giorno->toDateString(),
            'ora' => $ora,
            'turni' => $turni->count(),
            'email_inviate' => $inviate,
        ]);

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Legacy\Gda;
use App\Models\Legacy\Messaggio;
use App\Models\Legacy\Ritardo;
use App\Models\Legacy\SendEmail;
use App\Services\Comunicazioni;
use App\Services\ProvaNotifiche;
use Illuminate\Console\Command;

class InviaRitardi extends Command
{
    protected $signature = 'notifiche:ritardi {--prova-sicura : Limita la prova all’operatore e al cliente autorizzati: mail solo a lui e senza SMS, segnando comunque ritardo e coda}';

    protected $description = 'Invia le email di ritardo timbratura inizio/fine turno (modelli 2 e 3) e le comunicazioni turni in coda';

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $contesto = $prova->contesto($comunicazioni, (bool) $this->option('prova-sicura'));
        $idProva = $contesto['operatore'] ?? null;
        $idCliente = $contesto['cliente'] ?? null;
        // Come in Yii, a mezzanotte non si elabora nulla
        if (now()->hour === 0) {
            return self::SUCCESS;
        }

        $this->inviaComunicazioniInCoda($comunicazioni, $idProva);

        $this->info('Ritardi inizio turno: '.$this->elaboraRitardi($comunicazioni, Messaggio::find(2), 'I', $idProva, $idCliente));
        $this->info('Ritardi fine turno: '.$this->elaboraRitardi($comunicazioni, Messaggio::find(3), 'F', $idProva, $idCliente));

        return self::SUCCESS;
    }

    private function inviaComunicazioniInCoda(Comunicazioni $comunicazioni, ?int $idProva): void
    {
        $codaQuery = SendEmail::where('sent', 'N');

        if ($idProva !== null) {
            $codaQuery->where('id_operatore', $idProva)->whereRaw('LOWER(email) = ?', [ProvaNotifiche::EMAIL]);
        }

        foreach ($codaQuery->get() as $coda) {
            $comunicazioni->invia([$coda->email => (string) $coda->nome], 'Comunicazione turni', (string) $coda->testo, (int) $coda->id_operatore);

            $coda->update(['sent' => 'Y', 'data_sent' => now()]);
        }
    }

    /** Ritardo timbratura: $tipo 'I' = inizio turno, 'F' = fine turno. */
    private function elaboraRitardi(Comunicazioni $comunicazioni, ?Messaggio $config, string $tipo, ?int $idProva, ?int $idCliente): int
    {
        if (! $config) {
            return 0;
        }

        $inizio = $tipo === 'I';
        $colonna = $inizio ? 'inizio' : 'fine';
        $campoData = $inizio ? 't.data_inizio' : 't.data_fine';
        $campoOra = $inizio ? 't.ora_inizio' : 't.ora_fine';

        $giorno = today()->addDays((int) $config->giorni);
        $ora = now()->addDays((int) $config->giorni)->subMinutes((int) $config->minuti)->format('H:i');

        $turni = $comunicazioni->turni()
            ->when($idProva !== null, fn ($query) => $query->where('t.id_operatore', $idProva)->where('t.id_cliente', $idCliente))
            ->whereDate($campoData, $giorno)->where($campoOra, '<', $ora)->get();
        $notificati = 0;

        foreach ($turni as $turno) {
            if (Gda::where('id_turno', $turno->id)->where('tipo', $tipo)->exists()) {
                continue;
            }

            $ritardo = Ritardo::where('id_turno', $turno->id)->first();

            if ($ritardo && (int) $ritardo->{$colonna} >= (int) $config->numero_sms) {
                continue;
            }

            $ritardo ??= new Ritardo(['id_turno' => $turno->id, 'id_operatore' => $turno->id_operatore, 'inizio' => 0, 'fine' => 0]);
            $ritardo->{$colonna} = (int) $ritardo->{$colonna} + 1;
            $ritardo->{"data_ritardo_{$colonna}"} = now();
            $ritardo->save();

            $notificati++;

            $dati = (array) $turno + [
                'giorno' => $inizio ? $turno->inizio : $turno->fine,
                'orario' => $inizio ? $turno->ora_inizio : $turno->ora_fine,
            ];

            $comunicazioni->inviaSms($config, $dati, $turno->cellulare, (int) $turno->id_operatore);

            // Come in Yii, le email partono (operatore e admin) solo se l'operatore ha un indirizzo.
            if (! $config->invia_email || ! $turno->email) {
                continue;
            }
            $testo = $comunicazioni->testo($config->modello_email, $dati);
            $destinatari = $comunicazioni->destinatari($config->admin_email);
            $destinatari[$turno->email] = trim("{$turno->cognome} {$turno->nome}");

            $comunicazioni->invia($destinatari, (string) $config->oggetto, $testo, (int) $turno->id_operatore);
        }

        return $notificati;
    }
}

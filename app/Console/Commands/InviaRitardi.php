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
    use DiarioCron;

    protected $signature = 'notifiche:ritardi {--prova-sicura : Limita la prova all’operatore e al cliente autorizzati: mail a lui e agli admin del modello, senza SMS, segnando comunque ritardo e coda}';

    protected $description = 'Invia le email di ritardo timbratura inizio/fine turno (modelli 2 e 3) e le comunicazioni turni in coda';

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $this->diario('Avvio notifiche ritardi', [
            'prova_sicura' => (bool) $this->option('prova-sicura'),
        ]);

        $contesto = $prova->contesto($comunicazioni, (bool) $this->option('prova-sicura'));
        $idProva = $contesto['operatore'] ?? null;
        $idCliente = $contesto['cliente'] ?? null;

        // Come in Yii, a mezzanotte non si elabora nulla
        if (now()->hour === 0) {
            $this->diarioAttenzione('Ritardi saltati: a mezzanotte non si elabora nulla');

            return self::SUCCESS;
        }

        $coda = $this->inviaComunicazioniInCoda($comunicazioni, $idProva);
        $inizio = $this->elaboraRitardi($comunicazioni, Messaggio::find(2), 'I', $idProva, $idCliente);
        $fine = $this->elaboraRitardi($comunicazioni, Messaggio::find(3), 'F', $idProva, $idCliente);

        $this->diario('Ritardi completati', [
            'prova' => $idProva !== null,
            'operatore' => $idProva,
            'cliente' => $idCliente,
            'coda' => $coda,
            'inizio_notificati' => $inizio['notificati'],
            'inizio_saltati' => $inizio['saltati'],
            'fine_notificati' => $fine['notificati'],
            'fine_saltati' => $fine['saltati'],
        ]);

        return self::SUCCESS;
    }

    private function inviaComunicazioniInCoda(Comunicazioni $comunicazioni, ?int $idProva): int
    {
        $codaQuery = SendEmail::where('sent', 'N');

        if ($idProva !== null) {
            $codaQuery->where('id_operatore', $idProva)->whereRaw('LOWER(email) = ?', [ProvaNotifiche::EMAIL]);
        }

        $inviate = 0;

        foreach ($codaQuery->get() as $coda) {
            $esito = $comunicazioni->invia([$coda->email => (string) $coda->nome], 'Comunicazione turni', (string) $coda->testo, (int) $coda->id_operatore);
            $coda->update(['sent' => 'Y', 'data_sent' => now()]);
            $inviate += $esito;

            $this->diario('Comunicazione in coda inviata', [
                'id' => (int) $coda->id,
                'operatore' => (int) $coda->id_operatore,
                'email' => (string) $coda->email,
                'inviate' => $esito,
            ]);
        }

        if ($inviate === 0) {
            $this->diario('Coda comunicazioni vuota');
        }

        return $inviate;
    }

    /**
     * Ritardo timbratura: $tipo 'I' = inizio turno, 'F' = fine turno.
     *
     * @return array{notificati: int, saltati: int}
     */
    private function elaboraRitardi(Comunicazioni $comunicazioni, ?Messaggio $config, string $tipo, ?int $idProva, ?int $idCliente): array
    {
        if (! $config) {
            $this->diarioAttenzione('Modello ritardo assente', ['tipo' => $tipo]);

            return ['notificati' => 0, 'saltati' => 0];
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
        $saltati = 0;

        $this->diario('Controllo ritardi', [
            'tipo' => $tipo,
            'giorno' => $giorno->toDateString(),
            'ora_limite' => $ora,
            'turni' => $turni->count(),
        ]);

        foreach ($turni as $turno) {
            $riferimento = [
                'turno' => (int) $turno->id,
                'operatore' => (int) $turno->id_operatore,
                'tipo' => $tipo,
                'sede' => (string) $turno->descrizione,
                'giorno' => (string) ($inizio ? $turno->inizio : $turno->fine),
                'orario' => (string) ($inizio ? $turno->ora_inizio : $turno->ora_fine),
            ];

            if (Gda::where('id_turno', $turno->id)->where('tipo', $tipo)->exists()) {
                $saltati++;
                $this->diarioAttenzione('Ritardo saltato', $riferimento + ['motivo' => 'timbratura già presente']);

                continue;
            }

            $ritardo = Ritardo::where('id_turno', $turno->id)->first();

            if ($ritardo && (int) $ritardo->{$colonna} >= (int) $config->numero_sms) {
                $saltati++;
                $this->diarioAttenzione('Ritardo saltato', $riferimento + [
                    'motivo' => 'limite di avvisi raggiunto',
                    'avvisi' => (int) $ritardo->{$colonna},
                ]);

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

            $sms = $comunicazioni->inviaSms($config, $dati, $turno->cellulare, (int) $turno->id_operatore);
            $emailInviate = 0;
            $motivoEmail = null;

            // Come in Yii, le email partono (operatore e admin) solo se l'operatore ha un indirizzo.
            if (! $config->invia_email) {
                $motivoEmail = 'email disattivate sul modello';
            } elseif (! $turno->email) {
                $motivoEmail = 'operatore senza email';
            } else {
                $testo = $comunicazioni->testo($config->modello_email, $dati);
                $destinatari = $comunicazioni->destinatari($config->admin_email);
                $destinatari[$turno->email] = trim("{$turno->cognome} {$turno->nome}");
                $emailInviate = $comunicazioni->invia($destinatari, (string) $config->oggetto, $testo, (int) $turno->id_operatore);
            }

            $this->diario('Ritardo notificato', $riferimento + [
                'sms_inviati' => $sms['inviati'],
                'sms_falliti' => $sms['falliti'],
                'motivo_sms' => $sms['motivo'],
                'email_inviate' => $emailInviate,
                'email' => (string) $turno->email,
                'motivo_email' => $motivoEmail,
            ]);
        }

        return ['notificati' => $notificati, 'saltati' => $saltati];
    }
}

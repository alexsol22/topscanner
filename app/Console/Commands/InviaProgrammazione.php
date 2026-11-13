<?php

namespace App\Console\Commands;

use App\Enums\Ruolo;
use App\Mail\NotificaHtml;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Messaggio;
use App\Models\Legacy\Turno;
use App\Services\Comunicazioni;
use App\Services\ProvaNotifiche;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InviaProgrammazione extends Command
{
    use DiarioCron;

    protected $signature = 'notifiche:programmazione {--prova-sicura : Limita la prova all’operatore e al cliente autorizzati: mail solo a lui, senza SMS né modifiche}';

    protected $description = 'Invia agli admin il riepilogo ore richieste/programmate per cliente e sede nei prossimi 7 giorni';

    private const COLONNE_GIORNO = ['dom', 'lun', 'mar', 'mer', 'gio', 'ven', 'sab'];

    public const MODELLO = 6;

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $giorni = collect(range(0, 6))->map(fn (int $n) => today()->addDays($n));
        $config = Messaggio::find(self::MODELLO);

        $this->diario('Avvio programmazione', [
            'prova_sicura' => (bool) $this->option('prova-sicura'),
            'dal' => $giorni->first()->toDateString(),
            'al' => $giorni->last()->toDateString(),
        ]);

        if ($config && ! $config->invia_email) {
            $this->diarioAttenzione('Programmazione saltata: email disattivate sul modello');

            return self::SUCCESS;
        }

        $contesto = $prova->contesto($comunicazioni, (bool) $this->option('prova-sicura'));

        if ($contesto !== null) {
            $turni = Turno::query()
                ->where('id_operatore', $contesto['operatore'])
                ->where('id_cliente', $contesto['cliente'])
                ->whereBetween('data_inizio', [$giorni->first()->toDateString(), $giorni->last()->toDateString()])
                ->orderBy('data_inizio')
                ->orderBy('ora_inizio')
                ->get();
            $corpo = '<p>Programmazione dei soli turni dell’operatore di prova per il cliente prova.</p>';

            foreach ($turni as $turno) {
                $corpo .= '<p>'.e($turno->data_inizio->format('d-m-Y')).' '.e($turno->ora_inizio).' - '.e($turno->ora_fine).'</p>';
                $this->diario('Turno di prova in programmazione', [
                    'turno' => (int) $turno->id,
                    'operatore' => (int) $turno->id_operatore,
                    'cliente' => (int) $turno->id_cliente,
                    'giorno' => $turno->data_inizio->toDateString(),
                    'ora_inizio' => (string) $turno->ora_inizio,
                    'ora_fine' => (string) $turno->ora_fine,
                ]);
            }

            if ($turni->isEmpty()) {
                $corpo .= '<p>Nessun turno nei prossimi 7 giorni.</p>';
            }

            $periodo = $giorni->first()->format('d-m-Y').' - '.$giorni->last()->format('d-m-Y');
            $oggetto = $this->oggetto($periodo, $config);
            $comunicazioni->invia([ProvaNotifiche::EMAIL => 'Prova'], $oggetto, $this->introduzione($comunicazioni, $config, $periodo).$corpo, $contesto['operatore']);
            $this->diario('Programmazione di prova inviata', [
                'operatore' => $contesto['operatore'],
                'cliente' => $contesto['cliente'],
                'turni' => $turni->count(),
                'email' => ProvaNotifiche::EMAIL,
            ]);

            return self::SUCCESS;
        }

        $corpo = $this->corpo($giorni, true);

        $daModello = $comunicazioni->destinatari($config?->admin_email);
        $destinatari = $daModello !== [] ? $daModello : Admin::where('user_type', Ruolo::Admin->tipoLegacy())
            ->whereNotNull('email')->where('email', '!=', '')
            ->get()
            ->mapWithKeys(fn (Admin $admin) => [$admin->email => $admin->nome_completo])
            ->all();

        $periodo = $giorni->first()->format('d-m-Y').' - '.$giorni->last()->format('d-m-Y');
        $oggetto = $this->oggetto($periodo, $config);
        $messaggio = $this->introduzione($comunicazioni, $config, $periodo).$corpo;

        $inviate = $comunicazioni->invia($destinatari, $oggetto, $messaggio);
        $this->diario('Programmazione inviata', [
            'destinatari' => count($destinatari),
            'email_inviate' => $inviate,
            'indirizzi' => implode(', ', array_keys($destinatari)),
            'dal' => $giorni->first()->toDateString(),
            'al' => $giorni->last()->toDateString(),
        ]);

        return self::SUCCESS;
    }

    /** Email identica a quella inviata: introduzione del modello e riepilogo ore. */
    public function anteprima(Comunicazioni $comunicazioni, ?Messaggio $config): string
    {
        $giorni = collect(range(0, 6))->map(fn (int $n) => today()->addDays($n));
        $periodo = $giorni->first()->format('d-m-Y').' - '.$giorni->last()->format('d-m-Y');

        return NotificaHtml::renderizza(
            $this->oggetto($periodo, $config),
            $this->introduzione($comunicazioni, $config, $periodo).$this->corpo($giorni),
        );
    }

    /** @param  Collection<int, Carbon>  $giorni */
    private function corpo(Collection $giorni, bool $registra = false): string
    {
        $ore = Turno::query()
            ->whereBetween('data_inizio', [$giorni->first()->toDateString(), $giorni->last()->toDateString()])
            ->selectRaw('id_sede, data_inizio, SUM(ore) as totale')
            ->groupBy('id_sede', 'data_inizio')
            ->get()
            ->groupBy('id_sede');

        $corpo = '';

        foreach (Cliente::with('sedi')->get() as $cliente) {
            if ($registra) {
                $this->diario('Programmazione cliente inclusa', [
                    'cliente' => (int) $cliente->id,
                    'ragione_sociale' => (string) $cliente->ragione_sociale,
                    'sedi' => $cliente->sedi->count(),
                ]);
            }

            $corpo .= "<p><div> Cliente <b>{$cliente->ragione_sociale}</b> </div>";

            if ($cliente->sedi->isEmpty()) {
                $corpo .= '<div>Non sono state specificate sedi per il cliente</div>';
            }

            foreach ($cliente->sedi as $sede) {
                $corpo .= "<div> Sede <b>{$sede->descrizione}</b> {$sede->indirizzo}</div><br>---------------------------------------------------------------------------<br>";

                $orePerGiorno = $ore->get($sede->id, collect())->keyBy(fn ($riga) => Carbon::parse($riga->data_inizio)->toDateString());

                foreach ($giorni as $giorno) {
                    $corpo .= $this->riga($giorno, (float) ($sede->{self::COLONNE_GIORNO[$giorno->dayOfWeek]} ?? 0), (float) ($orePerGiorno->get($giorno->toDateString())?->totale ?? 0));
                }
            }

            $corpo .= '</p>';
        }

        return $corpo;
    }

    private function oggetto(string $periodo, ?Messaggio $config): string
    {
        $base = trim((string) $config?->oggetto);

        return $base !== '' ? $base.' '.$periodo : 'Programmazione '.$periodo;
    }

    private function introduzione(Comunicazioni $comunicazioni, ?Messaggio $config, string $periodo): string
    {
        $modello = trim((string) $config?->modello_email);

        if ($modello === '') {
            return '<p>Buongiorno</p><p>Qui di seguito la situazione della programmazione della settimana in corso</p>';
        }

        return $comunicazioni->testo($modello, ['giorno' => $periodo]);
    }

    private function riga(Carbon $giorno, float $richieste, float $programmate): string
    {
        $etichetta = ucfirst($giorno->locale('it')->translatedFormat('l d-m-Y'));

        if ($richieste > 0) {
            $differenza = $richieste - $programmate;
            $nota = match (true) {
                $differenza > 0 => " <br>- Mancano da programmare <b>{$differenza}</b> Ore ",
                $differenza < 0 => ' <br>- Sono state programmate <b>'.abs($differenza).'</b> Ore in più',
                default => '',
            };

            return "<div>{$etichetta} Ore richieste <b>{$richieste}</b> Ore programmate <b>{$programmate}</b> {$nota}</div>";
        }

        return $programmate > 0
            ? "<div>{$etichetta} <b>{$programmate}</b> Ore programmate </div>"
            : "<div>{$etichetta} Nessuna programmazione specificata </div>";
    }
}

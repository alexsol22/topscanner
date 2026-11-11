<?php

namespace App\Console\Commands;

use App\Enums\Ruolo;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Turno;
use App\Services\Comunicazioni;
use App\Services\ProvaNotifiche;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class InviaProgrammazione extends Command
{
    protected $signature = 'notifiche:programmazione {--prova-sicura : Limita la prova all’operatore e al cliente autorizzati: mail solo a lui, senza SMS né modifiche}';

    protected $description = 'Invia agli admin il riepilogo ore richieste/programmate per cliente e sede nei prossimi 7 giorni';

    private const COLONNE_GIORNO = ['dom', 'lun', 'mar', 'mer', 'gio', 'ven', 'sab'];

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $giorni = collect(range(0, 6))->map(fn (int $n) => today()->addDays($n));

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
            }

            if ($turni->isEmpty()) {
                $corpo .= '<p>Nessun turno nei prossimi 7 giorni.</p>';
            }

            $oggetto = 'Programmazione '.$giorni->first()->format('d-m-Y').' - '.$giorni->last()->format('d-m-Y');
            $comunicazioni->invia([ProvaNotifiche::EMAIL => 'Prova'], $oggetto, $corpo, $contesto['operatore']);
            $this->info('Programmazione di prova inviata a 1 destinatario');

            return self::SUCCESS;
        }

        $ore = Turno::query()
            ->whereBetween('data_inizio', [$giorni->first()->toDateString(), $giorni->last()->toDateString()])
            ->selectRaw('id_sede, data_inizio, SUM(ore) as totale')
            ->groupBy('id_sede', 'data_inizio')
            ->get()
            ->groupBy('id_sede');

        $corpo = '';

        foreach (Cliente::with('sedi')->get() as $cliente) {
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

        $destinatari = Admin::where('user_type', Ruolo::Admin->tipoLegacy())
            ->whereNotNull('email')->where('email', '!=', '')
            ->get()
            ->mapWithKeys(fn (Admin $admin) => [$admin->email => $admin->nome_completo])
            ->all();

        $oggetto = 'Programmazione '.$giorni->first()->format('d-m-Y').' - '.$giorni->last()->format('d-m-Y');
        $messaggio = '<p>Buongiorno</p><p>Qui di seguito la situazione della programmazione della settimana in corso</p>'.$corpo;

        $inviate = $comunicazioni->invia($destinatari, $oggetto, $messaggio);
        $this->info("Programmazione inviata a {$inviate} destinatari");

        return self::SUCCESS;
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

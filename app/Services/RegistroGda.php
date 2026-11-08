<?php

namespace App\Services;

use App\Models\Legacy\Cliente;
use App\Models\Legacy\Gda;
use App\Models\Legacy\Sede;
use App\Models\Legacy\Turno;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Dati del registro GDA stampabile: porting di gda::getGdaData (Yii). */
class RegistroGda
{
    /**
     * Un elemento per ogni turno con timbratura di inizio, ordinati per giorno, sede, postazione, operatore.
     *
     * @return array<int, array{data: string, sede: string, inizio: Gda, fine: ?Gda, ore: ?float, lavorate: ?float, giri: array<int, array<int, Gda>>}>
     */
    public function turni(User $utente, ?Carbon $da, ?Carbon $a, ?int $cliente, ?int $sede, ?int $operatore): array
    {
        $righe = Gda::with(['cliente', 'operatore', 'postazione'])
            ->whereIn('id_cliente', Cliente::visibiliA($utente)->select('tc_clienti.id'))
            ->when($da, fn ($q, $d) => $q->where(fn ($query) => $query
                ->whereDate('data', '>', $d->toDateString())
                ->orWhere(fn ($stessoGiorno) => $stessoGiorno
                    ->whereDate('data', $d->toDateString())
                    ->whereTime('orario', '>=', $d->format('H:i:s')))))
            ->when($a, fn ($q, $d) => $q->where(fn ($query) => $query
                ->whereDate('data', '<', $d->toDateString())
                ->orWhere(fn ($stessoGiorno) => $stessoGiorno
                    ->whereDate('data', $d->toDateString())
                    ->whereTime('orario', '<=', $d->format('H:i:s')))))
            ->when($cliente, fn ($q, $id) => $q->where('id_cliente', $id))
            ->when($sede, fn ($q, $id) => $q->where('id_sede', $id))
            ->when($operatore, fn ($q, $id) => $q->where('id_operatore', $id))
            ->orderBy('data')->orderBy('id_sede')->orderBy('id_postazione')->orderBy('id_operatore')->orderBy('orario')
            ->get();

        $nomiSedi = Sede::whereIn('id', $righe->pluck('id_sede')->unique())->pluck('descrizione', 'id');
        $oreTurni = Turno::whereIn('id', $righe->pluck('id_turno')->unique())->pluck('ore', 'id');

        $turni = [];

        foreach ($righe->where('tipo', Gda::INIZIO)->groupBy(fn (Gda $r) => $r->data->toDateString()) as $giorno => $inizi) {
            foreach ($inizi->unique('id_turno') as $inizio) {
                $delTurno = $righe->where('id_turno', $inizio->id_turno);
                $fine = $delTurno->firstWhere('tipo', Gda::FINE);

                $giri = $delTurno->where('tipo', Gda::PASSAGGIO)->whereNotNull('id_postazione')
                    ->groupBy('id_postazione')
                    ->map(fn ($passaggi) => $passaggi->values()->all())
                    ->values()->all();

                $turni[] = [
                    'data' => Carbon::parse($giorno)->format('d-m-Y'),
                    'sede' => (string) $nomiSedi->get($inizio->id_sede),
                    'inizio' => $inizio,
                    'fine' => $fine,
                    'ore' => $oreTurni->has($inizio->id_turno) ? (float) $oreTurni->get($inizio->id_turno) : null,
                    'lavorate' => $fine ? Calcoli::ore($inizio->rilevata_at, $fine->rilevata_at) : null,
                    'giri' => $giri,
                ];
            }
        }

        return $turni;
    }
}

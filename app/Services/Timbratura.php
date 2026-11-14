<?php

namespace App\Services;

use App\Models\Legacy\Codice;
use App\Models\Legacy\Gda;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Sede;
use App\Models\Legacy\Turno;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Timbratura: porting di Turni::checkTurno / scansionaTurno / apriChiudiTurno / sostituzione (Yii). Il QR deve appartenere alla sede del turno; per il QR sede servono GPS e coordinate della sede. */
class Timbratura
{
    /** Turno dell'operatore valido adesso (finestra ±15 minuti su ora_inizio / ora_fine, come checkTurno). */
    public function turnoAttivo(User $operatore): ?Turno
    {
        $ora = now()->format('H:i');
        $oggi = today()->toDateString();

        $turni = Turno::with(['sede.comune', 'sede.provincia', 'cliente'])
            ->where('id_operatore', $operatore->id)
            ->where(fn ($q) => $q->where('data_inizio', $oggi)->orWhere('data_fine', $oggi))
            ->orderBy('data_inizio')->orderBy('ora_inizio')
            ->get();

        foreach ($turni as $turno) {
            if ($turno->stato === 'F') {
                continue;
            }

            $fine = date('H:i', strtotime($turno->ora_fine) + 15 * 60);
            $inizio = date('H:i', strtotime($turno->ora_inizio) - 15 * 60);

            if ($ora <= $fine) {
                if ($inizio <= $ora) {
                    return $turno;
                }

                if ($oggi === $turno->data_fine->toDateString() && $oggi > $turno->data_inizio->toDateString()) {
                    return $turno;
                }

                return null;
            }
        }

        return null;
    }

    /** @return array{ok: bool, message: string, tipo?: string, ritardo?: int} */
    public function scansiona(User $operatore, string $codice, ?float $lat, ?float $lng, ?string $note = null): array
    {
        $turno = $this->turnoAttivo($operatore);

        if (! $turno) {
            return $this->ko('Nessun turno attivo per te in questo momento.');
        }

        return $this->scansionaTurno($turno, $operatore, $codice, $lat, $lng, $note);
    }

    /** @return array{ok: bool, message: string, tipo?: string, ritardo?: int} */
    public function scansionaTurno(Turno $turno, User $operatore, string $codice, ?float $lat, ?float $lng, ?string $note = null): array
    {
        $cod = Codice::where('codice', trim($codice))->where('stato', 'A')->first();

        if (! $cod || (int) $cod->id_sede !== (int) $turno->id_sede) {
            return $this->ko('Qrcode turno non riconosciuto');
        }

        $sede = $turno->sede;
        $km = 0.0;

        if ($cod->tipo === 'S') {
            if ($lat === null || $lng === null) {
                return $this->ko('Posizione non disponibile. Consenti il GPS per registrare la timbratura.');
            }

            if (! $this->sedeHaCoordinate($sede)) {
                return $this->ko('La sede non ha coordinate impostate. Contattare l\'amministrazione.');
            }

            $km = Calcoli::distanzaKm($lat, $lng, (float) $sede->lat, (float) $sede->lng);

            if (($km * 1000) > (float) $sede->distanza) {
                return $this->ko('La tua posizione risulta essere superiore a '.$sede->distanza.' metri dalla sede operativa. contattare l\'amministrazione');
            }
        }

        $postazione = null;
        $aggiorna = null;

        if ($cod->tipo === 'S') {
            if (! Gda::where('id_turno', $turno->id)->exists()) {
                $tipo = Gda::INIZIO;
                $msg = 'Inizio turno registrato con successo';
                $previsto = $turno->inizio;
                $aggiorna = 'I';
            } else {
                $tipo = Gda::FINE;
                $msg = 'Fine turno registrato con successo';
                $previsto = $turno->fine;
                $aggiorna = 'F';
            }
        } elseif ($cod->tipo === 'P') {
            $tipo = Gda::PASSAGGIO;
            $postazione = Postazione::where('id_codice', $cod->id)->first();
            $msg = 'Passaggio postazione '.$postazione?->titolo.' registrato con successo';
            $passaggi = Gda::where('id_turno', $turno->id)->where('tipo', 'P')->where('id_postazione', $postazione?->id)->count();
            $previsto = $turno->inizio->copy()->addMinutes(max(1, $passaggi) * (int) $postazione?->minuti);
        } else {
            return $this->ko('Qrcode turno non riconosciuto');
        }

        $ritardo = Calcoli::ritardoMinuti($previsto, now());

        Gda::create([
            'id_operatore' => $operatore->id,
            'id_cliente' => $turno->id_cliente,
            'id_sede' => $turno->id_sede,
            'id_turno' => $turno->id,
            'id_postazione' => $postazione?->id,
            'id_codice' => $cod->id,
            'data' => now()->toDateString(),
            'orario' => now()->format('H:i'),
            'note_operatore' => $note,
            'lat' => $lat,
            'lng' => $lng,
            'distanza' => $km,
            'ritardo' => $ritardo,
            'tipo' => $tipo,
        ]);

        if ($aggiorna) {
            $turno->stato = $aggiorna;
            if ($aggiorna === 'F') {
                $turno->ore_lavorate = $this->oreLavorate($turno);
            }
            $turno->save();
        }

        $msg .= $ritardo > 0
            ? ' — sei in ritardo di '.abs($ritardo).' minuti'
            : ' — sei in anticipo di '.abs($ritardo).' minuti';

        return ['ok' => true, 'tipo' => $tipo, 'ritardo' => $ritardo, 'message' => $msg];
    }

    /** Apertura/chiusura manuale (apriChiudiTurno): data/ora indicate dall'amministrazione. */
    public function apriChiudi(Turno $turno, User $autore, string $stato, Carbon $quando, ?string $note = null): void
    {
        $previsto = $stato === 'I' ? $turno->inizio : $turno->fine;

        Gda::create([
            'id_operatore' => $autore->id,
            'id_cliente' => $turno->id_cliente,
            'id_sede' => $turno->id_sede,
            'id_turno' => $turno->id,
            'id_postazione' => null,
            'id_codice' => $turno->sede?->id_codice,
            'data' => $quando->toDateString(),
            'orario' => $quando->format('H:i'),
            'note_operatore' => $note,
            'lat' => $turno->sede?->lat,
            'lng' => $turno->sede?->lng,
            'distanza' => 0,
            'ritardo' => Calcoli::ritardoMinuti($previsto, $quando),
            'tipo' => $stato,
        ]);

        $turno->stato = $stato;
        $turno->note_operatore = $note;
        if ($stato === 'F') {
            $turno->ore_lavorate = $this->oreLavorate($turno);
        }
        $turno->save();
    }

    /** L'operatore scansiona il codice sede e prende il turno programmato di oggi della sede (actionSostituisci). */
    public function sostituisci(User $operatore, string $codice, ?float $lat, ?float $lng, ?string $note = null): array
    {
        $cod = Codice::where('codice', trim($codice))->first();

        $turno = $cod ? Turno::where('id_sede', $cod->id_sede)
            ->where('stato', 'P')
            ->where(fn ($q) => $q->where('data_inizio', today()->toDateString())->orWhere('data_fine', today()->toDateString()))
            ->orderBy('data_inizio')->orderBy('ora_inizio')
            ->first() : null;

        if (! $turno) {
            return $this->ko('Turno non trovato');
        }

        $turno->id_operatore = $operatore->id;
        $turno->save();

        return $this->scansionaTurno($turno, $operatore, $codice, $lat, $lng, $note);
    }

    private function oreLavorate(Turno $turno): ?float
    {
        $inizio = Gda::where('id_turno', $turno->id)->where('tipo', 'I')->first();
        $fine = Gda::where('id_turno', $turno->id)->where('tipo', 'F')->first();

        return $inizio && $fine ? Calcoli::ore($inizio->rilevata_at, $fine->rilevata_at) : null;
    }

    private function sedeHaCoordinate(?Sede $sede): bool
    {
        return $sede !== null && ((float) $sede->lat !== 0.0 || (float) $sede->lng !== 0.0);
    }

    private function ko(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}

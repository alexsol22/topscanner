<?php

namespace App\Console\Commands;

use App\Models\Legacy\Gda;
use App\Models\Legacy\Messaggio;
use App\Models\Legacy\Ritardo;
use App\Models\Legacy\SendEmail;
use App\Models\Legacy\Turno;
use Illuminate\Console\Command;

class SegnaInviiNotifiche extends Command
{
    protected $signature = 'notifiche:segna-inviati';

    protected $description = 'Segna come già inviati ritardi e scadenze già maturati, senza inviare email';

    public function handle(): int
    {
        $inizio = $this->segnaRitardi(Messaggio::find(2), Gda::INIZIO);
        $fine = $this->segnaRitardi(Messaggio::find(3), Gda::FINE);
        $coda = SendEmail::query()->where('sent', 'N')->update(['sent' => 'Y', 'data_sent' => now()]);

        $this->segnaScadenze(4, 'Certificati');
        $this->segnaScadenze(5, 'Materiali');

        $this->info("Ritardi inizio segnati: {$inizio}");
        $this->info("Ritardi fine segnati: {$fine}");
        $this->info("Comunicazioni in coda segnate: {$coda}");
        $this->info('Gli avvisi turno non accumulano arretrati: partono solo sull’orario previsto.');

        return self::SUCCESS;
    }

    private function segnaRitardi(?Messaggio $config, string $tipo): int
    {
        if (! $config) {
            $this->warn('Modello ritardo '.($tipo === Gda::INIZIO ? 'inizio' : 'fine').' assente');

            return 0;
        }

        $inizio = $tipo === Gda::INIZIO;
        $colonna = $inizio ? 'inizio' : 'fine';
        $giorno = today()->addDays((int) $config->giorni)->toDateString();
        $ora = now()->addDays((int) $config->giorni)->subMinutes((int) $config->minuti)->format('H:i');
        $soglia = max(1, (int) $config->numero_sms);

        $turni = Turno::query()
            ->whereDate($inizio ? 'data_inizio' : 'data_fine', $giorno)
            ->where($inizio ? 'ora_inizio' : 'ora_fine', '<', $ora)
            ->whereDoesntHave('presenze', fn ($query) => $query->where('tipo', $tipo))
            ->get(['id', 'id_operatore']);

        if ($turni->isEmpty()) {
            return 0;
        }

        $esistenti = Ritardo::query()->whereIn('id_turno', $turni->pluck('id'))->get()->keyBy('id_turno');
        $segnati = 0;

        foreach ($turni as $turno) {
            $ritardo = $esistenti->get($turno->id);

            if ($ritardo && (int) $ritardo->{$colonna} >= $soglia) {
                continue;
            }

            $ritardo ??= new Ritardo([
                'id_turno' => $turno->id,
                'id_operatore' => $turno->id_operatore,
                'inizio' => 0,
                'fine' => 0,
            ]);
            $ritardo->{$colonna} = $soglia;
            $ritardo->{"data_ritardo_{$colonna}"} = now();
            $ritardo->save();
            $segnati++;
        }

        return $segnati;
    }

    private function segnaScadenze(int $id, string $etichetta): void
    {
        $config = Messaggio::find($id);

        if (! $config) {
            $this->warn("Modello {$etichetta} assente");

            return;
        }

        $limite = today()->addDays((int) $config->giorni)->toDateString();
        $config->segnato_fino_al = $limite;
        $config->save();
        $this->info("{$etichetta} segnati fino al {$limite}");
    }
}

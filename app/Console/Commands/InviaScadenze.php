<?php

namespace App\Console\Commands;

use App\Models\Legacy\Messaggio;
use App\Services\Comunicazioni;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InviaScadenze extends Command
{
    protected $signature = 'notifiche:scadenze';

    protected $description = 'Invia agli admin le scadenze di certificati (modello 4) e le riconsegne materiale (modello 5)';

    public function handle(Comunicazioni $comunicazioni): int
    {
        $this->info('Scadenze certificati: '.$this->inviaRiepilogo($comunicazioni, Messaggio::find(4), $this->certificati(...), 'scadenza_certificato'));
        $this->info('Riconsegne materiale: '.$this->inviaRiepilogo($comunicazioni, Messaggio::find(5), $this->materiali(...), 'scadenza_materiale'));

        return self::SUCCESS;
    }

    /** @param  callable(string): iterable<object>  $righe */
    private function inviaRiepilogo(Comunicazioni $comunicazioni, ?Messaggio $config, callable $righe, string $campoData): int
    {
        if (! $config?->invia_email) {
            return 0;
        }

        $destinatari = $comunicazioni->destinatari($config->admin_email);
        $limite = today()->addDays((int) $config->giorni)->toDateString();
        $testi = [];

        foreach ($righe($limite) as $riga) {
            $testi[] = $comunicazioni->testo($config->modello_email, (array) $riga);
        }

        if ($testi === [] || $destinatari === []) {
            return 0;
        }

        $comunicazioni->invia($destinatari, (string) $config->oggetto, implode('<br>', $testi));

        return count($testi);
    }

    private function certificati(string $limite)
    {
        return DB::connection('mysql')->table('tc_documenti as d')
            ->leftJoin('tc_admin as u', 'd.id_tipo', '=', 'u.id')
            ->leftJoin('_certificati as t', 'd.tipologia', '=', 't.id')
            ->where('d.tipo', 'O')
            ->whereNotNull('d.scadenza')
            ->whereDate('d.scadenza', '<=', $limite)
            ->selectRaw("CONCAT(t.nome, ' ', d.descrizione) as certificato, CONCAT(u.cognome, ' ', u.nome, ' COD ', u.codice) as operatore, DATE_FORMAT(d.scadenza, '%d-%m-%Y') as scadenza_certificato")
            ->get();
    }

    private function materiali(string $limite)
    {
        return DB::connection('mysql')->table('tc_materiali as d')
            ->leftJoin('tc_admin as u', 'd.id_operatore', '=', 'u.id')
            ->leftJoin('_materiali as m', 'd.id_materiale', '=', 'm.id')
            ->whereNotNull('d.riconsegna')
            ->whereDate('d.riconsegna', '<=', $limite)
            ->where('d.consegnato', 'N')
            ->selectRaw("CONCAT(m.nome, ' ', d.descrizione, ' Taglia ', d.taglia) as materiale, CONCAT(u.cognome, ' ', u.nome, ' COD ', u.codice) as operatore, DATE_FORMAT(d.riconsegna, '%d-%m-%Y') as scadenza_materiale")
            ->get();
    }
}

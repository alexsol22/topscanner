<?php

namespace App\Console\Commands;

use App\Models\Legacy\Messaggio;
use App\Services\Comunicazioni;
use App\Services\ProvaNotifiche;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InviaScadenze extends Command
{
    protected $signature = 'notifiche:scadenze {--prova-sicura : Limita la prova all’operatore autorizzato, senza SMS né modifiche}';

    protected $description = 'Invia agli admin le scadenze di certificati (modello 4) e le riconsegne materiale (modello 5)';

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $idProva = $this->option('prova-sicura') ? $prova->attiva($comunicazioni) : null;
        $this->info('Scadenze certificati: '.$this->inviaRiepilogo($comunicazioni, Messaggio::find(4), fn ($limite) => $this->certificati($limite, $idProva), $idProva));
        $this->info('Riconsegne materiale: '.$this->inviaRiepilogo($comunicazioni, Messaggio::find(5), fn ($limite) => $this->materiali($limite, $idProva), $idProva));

        return self::SUCCESS;
    }

    /** @param  callable(string): iterable<object>  $righe */
    private function inviaRiepilogo(Comunicazioni $comunicazioni, ?Messaggio $config, callable $righe, ?int $idProva): int
    {
        if (! $config?->invia_email) {
            return 0;
        }

        $destinatari = $idProva !== null ? [ProvaNotifiche::EMAIL => 'Prova'] : $comunicazioni->destinatari($config->admin_email);
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

    private function certificati(string $limite, ?int $idProva)
    {
        return DB::connection('mysql')->table('tc_documenti as d')
            ->leftJoin('tc_admin as u', 'd.id_tipo', '=', 'u.id')
            ->leftJoin('_certificati as t', 'd.tipologia', '=', 't.id')
            ->where('d.tipo', 'O')
            ->when($idProva !== null, fn ($query) => $query->where('d.id_tipo', $idProva))
            ->whereNotNull('d.scadenza')
            ->whereDate('d.scadenza', '<=', $limite)
            ->selectRaw("CONCAT(t.nome, ' ', d.descrizione) as certificato, CONCAT(u.cognome, ' ', u.nome, ' COD ', u.codice) as operatore, DATE_FORMAT(d.scadenza, '%d-%m-%Y') as scadenza_certificato")
            ->get();
    }

    private function materiali(string $limite, ?int $idProva)
    {
        return DB::connection('mysql')->table('tc_materiali as d')
            ->leftJoin('tc_admin as u', 'd.id_operatore', '=', 'u.id')
            ->leftJoin('_materiali as m', 'd.id_materiale', '=', 'm.id')
            ->whereNotNull('d.riconsegna')
            ->when($idProva !== null, fn ($query) => $query->where('d.id_operatore', $idProva))
            ->whereDate('d.riconsegna', '<=', $limite)
            ->where('d.consegnato', 'N')
            ->selectRaw("CONCAT(m.nome, ' ', d.descrizione, ' Taglia ', d.taglia) as materiale, CONCAT(u.cognome, ' ', u.nome, ' COD ', u.codice) as operatore, DATE_FORMAT(d.riconsegna, '%d-%m-%Y') as scadenza_materiale")
            ->get();
    }
}

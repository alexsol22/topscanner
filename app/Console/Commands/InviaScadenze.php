<?php

namespace App\Console\Commands;

use App\Models\Legacy\Messaggio;
use App\Services\Comunicazioni;
use App\Services\ProvaNotifiche;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InviaScadenze extends Command
{
    use DiarioCron;

    protected $signature = 'notifiche:scadenze {--prova-sicura : Limita la prova all’operatore e al cliente autorizzati: mail solo a lui, senza SMS né modifiche}';

    protected $description = 'Invia agli admin le scadenze di certificati (modello 4) e le riconsegne materiale (modello 5)';

    public function handle(Comunicazioni $comunicazioni, ProvaNotifiche $prova): int
    {
        $this->diario('Avvio scadenze', [
            'prova_sicura' => (bool) $this->option('prova-sicura'),
        ]);

        $idProva = $prova->contesto($comunicazioni, (bool) $this->option('prova-sicura'))['operatore'] ?? null;
        $certificatiConfig = Messaggio::find(4);
        $materialiConfig = Messaggio::find(5);
        $certificati = $this->inviaRiepilogo($comunicazioni, $certificatiConfig, 'certificati', fn ($limite) => $this->certificati($limite, $idProva, $certificatiConfig?->segnato_fino_al?->toDateString()), $idProva);
        $materiali = $this->inviaRiepilogo($comunicazioni, $materialiConfig, 'materiali', fn ($limite) => $this->materiali($limite, $idProva, $materialiConfig?->segnato_fino_al?->toDateString()), $idProva);

        $this->diario('Scadenze completate', [
            'prova' => $idProva !== null,
            'operatore' => $idProva,
            'certificati' => $certificati,
            'materiali' => $materiali,
        ]);

        return self::SUCCESS;
    }

    /** @param  callable(string): iterable<object>  $righe */
    private function inviaRiepilogo(Comunicazioni $comunicazioni, ?Messaggio $config, string $tipo, callable $righe, ?int $idProva): int
    {
        if (! $config?->invia_email) {
            $this->diarioAttenzione('Riepilogo scadenze saltato', [
                'tipo' => $tipo,
                'motivo' => $config === null ? 'modello assente' : 'email disattivate',
            ]);

            return 0;
        }

        $destinatari = $idProva !== null ? [ProvaNotifiche::EMAIL => 'Prova'] : $comunicazioni->destinatari($config->admin_email);
        $limite = today()->addDays((int) $config->giorni)->toDateString();
        $testi = [];

        foreach ($righe($limite) as $riga) {
            $testi[] = $comunicazioni->testo($config->modello_email, (array) $riga);
        }

        if ($testi === [] || $destinatari === []) {
            $this->diario('Riepilogo scadenze senza invio', [
                'tipo' => $tipo,
                'limite' => $limite,
                'righe' => count($testi),
                'destinatari' => count($destinatari),
            ]);

            return 0;
        }

        $inviate = $comunicazioni->invia($destinatari, (string) $config->oggetto, implode('<br>', $testi));

        $this->diario('Riepilogo scadenze inviato', [
            'tipo' => $tipo,
            'limite' => $limite,
            'righe' => count($testi),
            'email_inviate' => $inviate,
            'destinatari' => implode(', ', array_keys($destinatari)),
        ]);

        return count($testi);
    }

    private function certificati(string $limite, ?int $idProva, ?string $dopo = null)
    {
        return DB::connection('mysql')->table('tc_documenti as d')
            ->leftJoin('tc_admin as u', 'd.id_tipo', '=', 'u.id')
            ->leftJoin('_certificati as t', 'd.tipologia', '=', 't.id')
            ->where('d.tipo', 'O')
            ->when($idProva !== null, fn ($query) => $query->where('d.id_tipo', $idProva))
            ->whereNotNull('d.scadenza')
            ->when($dopo !== null, fn ($query) => $query->whereDate('d.scadenza', '>', $dopo))
            ->whereDate('d.scadenza', '<=', $limite)
            ->selectRaw("CONCAT(t.nome, ' ', d.descrizione) as certificato, CONCAT(u.cognome, ' ', u.nome, ' COD ', u.codice) as operatore, DATE_FORMAT(d.scadenza, '%d-%m-%Y') as scadenza_certificato")
            ->get();
    }

    private function materiali(string $limite, ?int $idProva, ?string $dopo = null)
    {
        return DB::connection('mysql')->table('tc_materiali as d')
            ->leftJoin('tc_admin as u', 'd.id_operatore', '=', 'u.id')
            ->leftJoin('_materiali as m', 'd.id_materiale', '=', 'm.id')
            ->whereNotNull('d.riconsegna')
            ->when($idProva !== null, fn ($query) => $query->where('d.id_operatore', $idProva))
            ->when($dopo !== null, fn ($query) => $query->whereDate('d.riconsegna', '>', $dopo))
            ->whereDate('d.riconsegna', '<=', $limite)
            ->where('d.consegnato', 'N')
            ->selectRaw("CONCAT(m.nome, ' ', d.descrizione, ' Taglia ', d.taglia) as materiale, CONCAT(u.cognome, ' ', u.nome, ' COD ', u.codice) as operatore, DATE_FORMAT(d.riconsegna, '%d-%m-%Y') as scadenza_materiale")
            ->get();
    }
}

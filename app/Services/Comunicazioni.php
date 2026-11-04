<?php

namespace App\Services;

use App\Mail\NotificaHtml;
use App\Models\Legacy\Email;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Invio email automatiche: porting di MyEmailsNative / MyUtils::getTextMessage (Yii). */
class Comunicazioni
{
    /** @param  array<string, mixed>  $dati */
    public function testo(?string $modello, array $dati): string
    {
        $sostituzioni = [
            '[OPERATORE]' => trim(($dati['cognome'] ?? '').' '.($dati['nome'] ?? '')),
            '[DATA TURNO]' => $dati['giorno'] ?? '',
            '[ORA TURNO]' => $dati['orario'] ?? '',
            '[SEDE]' => $dati['descrizione'] ?? '',
            '[CERTIFICATO]' => $dati['certificato'] ?? '',
            '[DATA CERTIFICATO]' => $dati['scadenza_certificato'] ?? '',
            '[MATERIALE]' => $dati['materiale'] ?? '',
            '[DATA MATERIALE]' => $dati['scadenza_materiale'] ?? '',
            '[OPERATORE MATERIALE]' => $dati['operatore'] ?? '',
            '[OPERATORE CERTIFICATO]' => $dati['operatore'] ?? '',
            '[INDIRIZZO]' => trim(($dati['indirizzo'] ?? '').' '.($dati['comune'] ?? '').' ('.($dati['sigla'] ?? '').')'),
        ];

        return strtr((string) $modello, $sostituzioni);
    }

    /** Turni con operatore e sede, con gli stessi campi usati dai modelli messaggio. */
    public function turni(): Builder
    {
        return DB::connection('mysql')->table('tc_turni as t')
            ->leftJoin('tc_sedi as s', 't.id_sede', '=', 's.id')
            ->leftJoin('tc_admin as a', 't.id_operatore', '=', 'a.id')
            ->leftJoin('_comuni as c', 's.citta_id', '=', 'c.id')
            ->leftJoin('_provincia as p', 's.provincia_id', '=', 'p.id')
            ->select('t.*', 'c.comune', 'p.sigla', 's.indirizzo', 's.cap', 'a.nome', 'a.cognome', 'a.cellulare', 'a.codice', 'a.email', 's.descrizione')
            ->selectRaw("DATE_FORMAT(t.data_inizio, '%d-%m-%Y') as inizio, DATE_FORMAT(t.data_fine, '%d-%m-%Y') as fine");
    }

    /** @return array<string, string> indirizzo => nome */
    public function destinatari(?string $lista, string $nome = 'Amministrazione'): array
    {
        $destinatari = [];

        foreach (explode(',', (string) $lista) as $indirizzo) {
            $indirizzo = trim($indirizzo);

            if (filter_var($indirizzo, FILTER_VALIDATE_EMAIL)) {
                $destinatari[$indirizzo] = $nome;
            }
        }

        return $destinatari;
    }

    /**
     * Invia una email con il template di tc_config e la registra in tc_email.
     *
     * @param  array<string, string>  $destinatari  indirizzo => nome
     */
    public function invia(array $destinatari, string $oggetto, string $messaggio, int $idOperatore = 0): int
    {
        $html = NotificaHtml::renderizza($oggetto, $messaggio);
        $inviate = 0;

        foreach ($destinatari as $indirizzo => $nome) {
            try {
                Mail::to($indirizzo, $nome ?: null)->send(new NotificaHtml($oggetto, $messaggio));
            } catch (Throwable $e) {
                Log::error("Invio email a {$indirizzo} fallito: {$e->getMessage()}");

                continue;
            }

            Email::create([
                'id_operatore' => $idOperatore,
                'oggetto' => $oggetto,
                'email' => $html,
                'destinatario' => json_encode([$indirizzo => $nome]),
                'data_invio' => now(),
            ]);
            $inviate++;
        }

        return $inviate;
    }
}

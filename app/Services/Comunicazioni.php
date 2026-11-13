<?php

namespace App\Services;

use App\Mail\NotificaHtml;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Email;
use App\Models\Legacy\Messaggio;
use App\Models\Legacy\SendEmail;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Invio email automatiche: porting di MyEmailsNative / MyUtils::getTextMessage (Yii). */
class Comunicazioni
{
    private ?string $destinatarioProva = null;

    public function __construct(private ?Sms $sms = null) {}

    public function attivaProva(string $destinatario): void
    {
        $this->destinatarioProva = $destinatario;
    }

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
        if ($this->destinatarioProva !== null) {
            $inviate = 0;

            foreach ($destinatari as $indirizzo => $nome) {
                try {
                    Mail::to($indirizzo, $nome ?: null)->send(new NotificaHtml('[PROVA] '.$oggetto, $messaggio));
                } catch (Throwable $e) {
                    Log::error("Invio email di prova a {$indirizzo} fallito: {$e->getMessage()}");

                    continue;
                }

                $inviate++;
            }

            return $inviate;
        }

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

    /**
     * Come nei job Yii: SMS all'operatore e poi a ogni numero di admin_sms del modello (solo se invia_sms è attivo).
     *
     * @param  array<string, mixed>  $dati
     * @return array{inviati: int, falliti: int, motivo: string|null}
     */
    public function inviaSms(Messaggio $config, array $dati, ?string $cellulare, int $idOperatore): array
    {
        if ($this->destinatarioProva !== null) {
            return ['inviati' => 0, 'falliti' => 0, 'motivo' => 'prova sicura'];
        }

        if (! $config->invia_sms) {
            return ['inviati' => 0, 'falliti' => 0, 'motivo' => 'sms disattivati sul modello'];
        }

        if ($cellulare === null || trim($cellulare) === '') {
            return ['inviati' => 0, 'falliti' => 0, 'motivo' => 'operatore senza cellulare'];
        }

        $sms = $this->sms ??= app(Sms::class);

        if (! $sms->abilitato()) {
            return ['inviati' => 0, 'falliti' => 0, 'motivo' => 'invio SMS disattivato'];
        }

        $testo = $this->testo($config->modello_sms, $dati);
        $mittente = (string) $config->mittente;
        $numeri = [trim($cellulare)];

        foreach (explode(',', (string) $config->admin_sms) as $numero) {
            $numero = trim($numero);

            if ($numero !== '') {
                $numeri[] = $numero;
            }
        }

        $inviati = 0;

        foreach ($numeri as $numero) {
            if ($sms->invia($numero, $testo, $mittente, $idOperatore)) {
                $inviati++;
            }
        }

        return ['inviati' => $inviati, 'falliti' => count($numeri) - $inviati, 'motivo' => null];
    }

    /** Come MyEmailsNative::sendEmailAccessi: credenziali provvisorie di un nuovo account (non registrate in tc_email per non salvare la password). */
    public function inviaCredenziali(string $nome, string $utente, string $email, string $password): bool
    {
        try {
            Mail::to($email, $nome ?: null)->send(new NotificaHtml(
                'Area Riservata',
                view('emails.credenziali-accesso', ['nome' => $nome, 'utente' => $utente, 'password' => $password])->render(),
                ['etichetta' => 'Accedi all\'area riservata', 'url' => route('login')],
            ));
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    /**
     * Come Turni::sendEmailTurni (Yii): una email di promemoria per operatore con i suoi turni filtrati,
     * accodata in tc_send_email e spedita dal comando notifiche:ritardi.
     *
     * @param  array{operatore?: ?int, sede?: ?int, cliente?: ?int, inizio?: ?string, fine?: ?string}  $filtri
     * @return array{codice: int, operatori: int}|null null se nessun turno corrisponde ai filtri
     */
    public function accodaPromemoriaTurni(User $utente, array $filtri, string $modello): ?array
    {
        $turni = $this->turni()
            ->whereIn('t.id_cliente', Cliente::visibiliA($utente)->select('tc_clienti.id'))
            ->when($filtri['operatore'] ?? null, fn ($q, $id) => $q->where('t.id_operatore', $id))
            ->when($filtri['sede'] ?? null, fn ($q, $id) => $q->where('t.id_sede', $id))
            ->when($filtri['cliente'] ?? null, fn ($q, $id) => $q->where('t.id_cliente', $id))
            ->when($filtri['inizio'] ?? null, fn ($q, $data) => $q->where('t.data_inizio', '>=', $data))
            ->when($filtri['fine'] ?? null, fn ($q, $data) => $q->where('t.data_inizio', '<=', $data))
            ->get();

        if ($turni->isEmpty()) {
            return null;
        }

        $codice = time();
        $perOperatore = $turni->groupBy('id_operatore');

        foreach ($perOperatore as $idOperatore => $righe) {
            $operatore = $righe->first();
            $nome = trim("{$operatore->cognome} {$operatore->nome}");
            $testo = "<p>Ciao {$nome} Qui di seguito un promemoria dei tuoi prossimi turni </p>";

            foreach ($righe as $riga) {
                $testo .= "<p style='margin-bottom:15px'>".$this->testo($modello, (array) $riga + ['giorno' => $riga->inizio, 'orario' => $riga->ora_inizio]).'</p>';
            }

            SendEmail::create([
                'code' => $codice,
                'email' => $operatore->email,
                'nome' => $nome,
                'id_operatore' => $idOperatore,
                'testo' => $testo,
                'sent' => 'N',
            ]);
        }

        return ['codice' => $codice, 'operatori' => $perOperatore->count()];
    }
}

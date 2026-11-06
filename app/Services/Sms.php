<?php

namespace App\Services;

use App\Models\Legacy\SendSms;
use App\Models\Legacy\SendSmsDelivery;
use App\Models\Legacy\SendSmsResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Invio SMS e registrazione consegne: porting di MySms (Yii). Inattivo finché config('sms.enabled') è false. */
class Sms
{
    public function abilitato(): bool
    {
        return (bool) config('sms.enabled');
    }

    /** Come MySms::_sendSms: token, riga in tc_send_sms, invio, esito in tc_send_sms_result. */
    public function invia(string $numero, string $testo, ?string $mittente = null, int $idOperatore = 0): bool
    {
        $numero = trim($numero);

        if ($numero === '') {
            return false;
        }

        if (! $this->abilitato()) {
            Log::info("SMS disattivati: messaggio a {$numero} non inviato.");

            return false;
        }

        $mittente = $mittente !== null && $mittente !== '' ? $mittente : (string) config('sms.sender');

        try {
            $token = $this->token();

            if (! $token) {
                return false;
            }

            $sms = SendSms::create([
                'destinatari' => $numero,
                'sender' => $mittente,
                'testo' => $testo,
                'quanti' => count(explode(',', $numero)),
                'data_invio' => now(),
                'id_operatore' => $idOperatore,
            ]);

            $risposta = $this->chiamata()->asForm()->post($this->url('send').'?token='.$token, [
                'sms_text' => $testo,
                'sms_type' => config('sms.type'),
                'sms_sender' => $mittente,
                'numbers' => $numero,
                'delivery_url' => route('sms.consegna'),
            ])->json() ?? [];
        } catch (Throwable $e) {
            Log::error("Invio SMS a {$numero} fallito: {$e->getMessage()}");

            return false;
        }

        $riuscito = ($risposta['success'] ?? false) === true;

        SendSmsResult::create([
            'send_id' => $sms->id,
            'adl_id' => $risposta['data']['id_send'] ?? null,
            'credits' => $risposta['data']['credits'] ?? null,
            'costo' => $risposta['data']['costo'] ?? 0,
            'dest' => $risposta['data']['dest'] ?? null,
            'response' => $riuscito
                ? ($risposta['data']['message'] ?? null)
                : trim(($risposta['error']['code'] ?? '').' '.($risposta['error']['message'] ?? '').' '.($risposta['error']['field'] ?? '')),
            'message' => $testo,
            'numero' => $numero,
        ]);

        $sms->update(['sent' => $riuscito ? 'Y' : 'N']);

        return $riuscito;
    }

    /** Come MySms::getCredits; null se gli SMS sono disattivati o il provider non risponde. */
    public function crediti(): ?float
    {
        if (! $this->abilitato()) {
            return null;
        }

        try {
            $token = $this->token();

            if (! $token) {
                return null;
            }

            $crediti = $this->chiamata()->get($this->url('credits'), ['token' => $token])->json('data.credits');
        } catch (Throwable $e) {
            Log::error("Lettura crediti SMS fallita: {$e->getMessage()}");

            return null;
        }

        return $crediti === null ? null : (float) $crediti;
    }

    /**
     * Come MySms::saveDelivery: registra la consegna notificata dal provider (id_send, delivery_number, delivery_status, delivery_data).
     *
     * @param  array<string, mixed>  $dati
     */
    public function registraConsegna(array $dati): SendSmsDelivery
    {
        $idSend = $dati['id_send'] ?? null;
        $stato = (string) ($dati['delivery_status'] ?? '');

        return SendSmsDelivery::create([
            'refer_id' => $idSend,
            'send_id' => $idSend === null ? null : SendSmsResult::where('adl_id', $idSend)->value('send_id'),
            'numero' => $dati['delivery_number'] ?? null,
            'delivery_status' => $stato,
            'delivery_reason' => $stato === 'D' ? 'Consegnato' : 'Fallito',
            'delivery_data' => $dati['delivery_data'] ?? null,
        ]);
    }

    private function token(): ?string
    {
        $risposta = $this->chiamata()->asForm()->post($this->url('auth'), [
            'username' => config('sms.username'),
            'password' => config('sms.password'),
        ])->json() ?? [];

        return ($risposta['success'] ?? false) === true ? ($risposta['data']['token'] ?? null) : null;
    }

    private function chiamata()
    {
        return Http::timeout((int) config('sms.timeout'));
    }

    private function url(string $percorso): string
    {
        return rtrim((string) config('sms.api_url'), '/').'/'.$percorso;
    }
}

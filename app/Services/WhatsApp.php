<?php

namespace App\Services;

use App\Enums\WhatsappStato;
use App\Models\Legacy\SendWhatsapp;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Invio WhatsApp Cloud API e aggiornamento stati dal webhook. Inattivo finché config('whatsapp.enabled') è false. */
class WhatsApp
{
    public function abilitato(): bool
    {
        return (bool) config('whatsapp.enabled');
    }

    /**
     * Chiavi ancora vuote per lanciare whatsapp:prova.
     *
     * @return list<string>
     */
    public function requisitiMancanti(?string $numero, ?string $template): array
    {
        $mancanti = [];

        if (! $this->abilitato()) {
            $mancanti[] = 'WHATSAPP_ENABLED=true';
        }

        if (! filled(config('whatsapp.token'))) {
            $mancanti[] = 'WHATSAPP_TOKEN';
        }

        if (! filled(config('whatsapp.phone_number_id'))) {
            $mancanti[] = 'WHATSAPP_PHONE_NUMBER_ID';
        }

        if (trim((string) $numero) === '') {
            $mancanti[] = 'WHATSAPP_TEST_TO oppure il numero nel comando';
        }

        if (trim((string) $template) === '') {
            $mancanti[] = 'WHATSAPP_TEST_TEMPLATE';
        }

        return $mancanti;
    }

    /**
     * @param  list<string>  $parametri
     */
    public function inviaTemplate(string $numero, string $template, string $lingua, array $parametri = [], int $idOperatore = 0): ?SendWhatsapp
    {
        $numero = $this->numero($numero);
        $template = trim($template);

        if ($numero === '' || $template === '' || ! $this->pronto()) {
            return null;
        }

        $corpo = [
            'messaging_product' => 'whatsapp',
            'to' => $numero,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lingua !== '' ? $lingua : (string) config('whatsapp.lingua_prova')],
            ],
        ];

        if ($parametri !== []) {
            $corpo['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $parametro): array => ['type' => 'text', 'text' => $parametro],
                    $parametri,
                ),
            ]];
        }

        return $this->invia($numero, 'template', $corpo, $template, $lingua, $parametri, $idOperatore);
    }

    public function inviaTesto(string $numero, string $testo, int $idOperatore = 0): ?SendWhatsapp
    {
        $numero = $this->numero($numero);
        $testo = trim($testo);

        if ($numero === '' || $testo === '' || ! $this->pronto()) {
            return null;
        }

        return $this->invia($numero, 'testo', [
            'messaging_product' => 'whatsapp',
            'to' => $numero,
            'type' => 'text',
            'text' => ['body' => $testo],
        ], null, null, [], $idOperatore, $testo);
    }

    /** @param  array<string, mixed>  $payload */
    public function registraNotifiche(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change)) {
                    continue;
                }

                foreach ($change['value']['statuses'] ?? [] as $stato) {
                    if (is_array($stato)) {
                        $this->applicaStato($stato);
                    }
                }
            }
        }
    }

    public function numero(string $numero): string
    {
        $numero = preg_replace('/\D+/', '', $numero) ?? '';

        if (str_starts_with($numero, '00')) {
            $numero = substr($numero, 2);
        }

        return $numero;
    }

    /**
     * @param  array<string, mixed>  $corpo
     * @param  list<string>  $parametri
     */
    private function invia(
        string $numero,
        string $tipo,
        array $corpo,
        ?string $template,
        ?string $lingua,
        array $parametri,
        int $idOperatore,
        ?string $testo = null,
    ): SendWhatsapp {
        $riga = SendWhatsapp::create([
            'destinatario' => $numero,
            'tipo' => $tipo,
            'template' => $template,
            'lingua' => $lingua,
            'testo' => $testo ?? $template,
            'parametri' => $parametri === [] ? null : $parametri,
            'stato' => WhatsappStato::InAttesa,
            'id_operatore' => $idOperatore,
            'data_invio' => now(),
        ]);

        try {
            $risposta = $this->chiamata()->post($this->urlMessaggi(), $corpo);
        } catch (Throwable $e) {
            Log::error("Invio WhatsApp a {$numero} fallito: {$e->getMessage()}");
            $riga->update([
                'stato' => WhatsappStato::Fallito,
                'errore' => $e->getMessage(),
            ]);

            return $riga;
        }

        return $this->registraRisposta($riga, $risposta);
    }

    private function registraRisposta(SendWhatsapp $riga, Response $risposta): SendWhatsapp
    {
        $wamid = $risposta->json('messages.0.id');

        if ($risposta->successful() && is_string($wamid) && $wamid !== '') {
            $riga->update([
                'wamid' => $wamid,
                'stato' => WhatsappStato::Inviato,
                'errore' => null,
            ]);

            return $riga;
        }

        $errore = (string) ($risposta->json('error.message') ?: 'Risposta Meta senza id messaggio');
        Log::error("Invio WhatsApp a {$riga->destinatario} rifiutato: {$errore}");
        $riga->update([
            'stato' => WhatsappStato::Fallito,
            'errore' => $errore,
        ]);

        return $riga;
    }

    /** @param  array<string, mixed>  $stato */
    private function applicaStato(array $stato): void
    {
        $wamid = (string) ($stato['id'] ?? '');
        $nuovo = WhatsappStato::daMeta((string) ($stato['status'] ?? ''));

        if ($wamid === '' || $nuovo === null) {
            return;
        }

        $messaggio = SendWhatsapp::query()->where('wamid', $wamid)->first();

        if ($messaggio === null) {
            return;
        }

        $quando = isset($stato['timestamp'])
            ? CarbonImmutable::createFromTimestamp((int) $stato['timestamp'], (string) config('app.timezone'))
            : CarbonImmutable::now();

        if ($messaggio->aggiornato_il !== null && $quando->lessThanOrEqualTo($messaggio->aggiornato_il)) {
            return;
        }

        $errore = null;

        if ($nuovo === WhatsappStato::Fallito) {
            $errore = (string) ($stato['errors'][0]['message'] ?? $stato['errors'][0]['title'] ?? 'Invio fallito');
        }

        $messaggio->update([
            'stato' => $nuovo,
            'errore' => $errore,
            'aggiornato_il' => $quando,
        ]);
    }

    private function pronto(): bool
    {
        if (! $this->abilitato()) {
            Log::info('WhatsApp disattivato: messaggio non inviato.');

            return false;
        }

        if (! filled(config('whatsapp.token')) || ! filled(config('whatsapp.phone_number_id'))) {
            Log::error('WhatsApp attivo ma WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID mancante.');

            return false;
        }

        return true;
    }

    private function chiamata()
    {
        return Http::withToken((string) config('whatsapp.token'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('whatsapp.timeout'));
    }

    private function urlMessaggi(): string
    {
        return rtrim((string) config('whatsapp.graph_url'), '/')
            .'/'.trim((string) config('whatsapp.api_version'), '/')
            .'/'.config('whatsapp.phone_number_id')
            .'/messages';
    }
}

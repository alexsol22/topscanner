<?php

namespace App\Mail;

use App\Models\Legacy\Config;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotificaHtml extends Mailable
{
    /**
     * @param  array{etichetta: string, url: string}|null  $azione  pulsante opzionale
     */
    public function __construct(public string $oggettoEmail, public string $corpoHtml, public ?array $azione = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->oggettoEmail);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->corpoRenderizzato());
    }

    public function corpoRenderizzato(): string
    {
        return self::renderizza($this->oggettoEmail, $this->corpoHtml, $this->azione);
    }

    /**
     * @param  array{etichetta: string, url: string}|null  $azione
     */
    public static function renderizza(string $oggetto, string $corpoHtml, ?array $azione = null): string
    {
        $config = Config::first();
        $colore = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $config?->colore_primario) ? $config->colore_primario : '#0ea5e9';
        $indirizzo = trim(html_entity_decode(implode(' ', array_filter([
            $config?->societa_indirizzo,
            trim($config?->societa_cap.' '.$config?->societa_comune.($config?->societa_provincia ? ' ('.$config->societa_provincia.')' : '')),
        ]))));

        return view('emails.notifica', [
            'oggetto' => $oggetto,
            'corpoHtml' => $corpoHtml,
            'azione' => $azione,
            'colore' => $colore,
            'app' => $config?->app ?: config('app.name'),
            'logo' => asset('brand/logo-security-scanner.png'),
            'societa' => [
                'ragione_sociale' => $config?->ragione_sociale ?: config('app.name'),
                'indirizzo' => $indirizzo,
                'telefono' => (string) ($config?->societa_telefono ?: $config?->phone),
                'email' => (string) ($config?->societa_email ?: $config?->email),
                'iva' => (string) $config?->societa_iva,
            ],
        ])->render();
    }
}

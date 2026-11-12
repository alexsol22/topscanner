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
        $app = trim((string) config('app.name'));
        $parti = preg_split('/\s+/', $app, 2) ?: [$app];
        $indirizzo = trim(html_entity_decode(implode(' ', array_filter([
            $config?->societa_indirizzo,
            trim($config?->societa_cap.' '.$config?->societa_comune.($config?->societa_provincia ? ' ('.$config->societa_provincia.')' : '')),
        ]))));

        return view('emails.notifica', [
            'oggetto' => $oggetto,
            'corpoHtml' => $corpoHtml,
            'azione' => $azione,
            'app' => $app,
            'marca' => $parti[0],
            'marchio' => $parti[1] ?? '',
            'logo' => self::logoIncorporato(),
            'societa' => [
                'ragione_sociale' => $config?->ragione_sociale ?: config('app.name'),
                'indirizzo' => $indirizzo,
                'telefono' => (string) ($config?->societa_telefono ?: $config?->phone),
                'email' => (string) ($config?->societa_email ?: $config?->email),
                'iva' => (string) $config?->societa_iva,
            ],
        ])->render();
    }

    /**
     * L'immagine viaggia nel messaggio: un URL locale non è raggiungibile dal client di posta.
     */
    private static function logoIncorporato(): string
    {
        $percorso = public_path('brand/icon-192.png');
        $contenuto = is_file($percorso) ? file_get_contents($percorso) : false;

        if ($contenuto === false || $contenuto === '') {
            return rtrim((string) config('app.url'), '/').'/brand/icon-192.png';
        }

        return 'data:image/png;base64,'.base64_encode($contenuto);
    }
}

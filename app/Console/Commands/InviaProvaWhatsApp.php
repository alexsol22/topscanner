<?php

namespace App\Console\Commands;

use App\Enums\WhatsappStato;
use App\Services\WhatsApp;
use Illuminate\Console\Command;

class InviaProvaWhatsApp extends Command
{
    protected $signature = 'whatsapp:prova {numero? : Numero internazionale, con o senza +. Default: WHATSAPP_TEST_TO} {--param=* : Parametri body del template, in ordine}';

    protected $description = 'Invia il template di prova WhatsApp al numero indicato';

    public function handle(WhatsApp $whatsapp): int
    {
        $numero = $this->argument('numero') ?: config('whatsapp.destinatario_prova');
        $template = (string) config('whatsapp.template_prova');
        $mancanti = $whatsapp->requisitiMancanti(is_string($numero) ? $numero : null, $template);

        if ($mancanti !== []) {
            $this->error('WhatsApp non è pronto. Imposta in .env: '.implode(', ', $mancanti).'.');

            return self::FAILURE;
        }

        $parametri = array_values(array_filter(
            $this->option('param'),
            fn (mixed $parametro): bool => is_string($parametro) && $parametro !== '',
        ));

        $inviato = $whatsapp->inviaTemplate(
            (string) $numero,
            $template,
            (string) config('whatsapp.lingua_prova'),
            $parametri,
        );

        if ($inviato === null) {
            $this->error('Invio non eseguito.');

            return self::FAILURE;
        }

        if ($inviato->stato === WhatsappStato::Fallito) {
            $this->error($inviato->errore ?: 'Invio rifiutato da Meta.');

            return self::FAILURE;
        }

        $this->info("WhatsApp inviato a {$inviato->destinatario}. Id Meta: {$inviato->wamid}");

        return self::SUCCESS;
    }
}

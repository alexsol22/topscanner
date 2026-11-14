<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Verifica e stati di consegna della WhatsApp Cloud API. */
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, WhatsApp $whatsapp): Response
    {
        if ($request->isMethod('GET')) {
            return $this->verifica($request);
        }

        $segreto = (string) config('whatsapp.app_secret');
        $firma = (string) $request->header('X-Hub-Signature-256');
        $attesa = 'sha256='.hash_hmac('sha256', $request->getContent(), $segreto);

        abort_unless($segreto !== '' && hash_equals($attesa, $firma), 403);

        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return response('Payload non valido', 400);
        }

        $whatsapp->registraNotifiche($payload);

        return response('OK');
    }

    private function verifica(Request $request): Response
    {
        $token = (string) config('whatsapp.verify_token');

        abort_unless(
            $token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token')),
            403,
        );

        return response((string) $request->query('hub_challenge'));
    }
}

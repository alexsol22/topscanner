<?php

return [

    /*
    | WhatsApp resta disattivato finché WHATSAPP_ENABLED non è true: nessuna chiamata
    | a Meta e nessuna riga in tc_send_whatsapp.
    |
    | Per la prova servono un token permanente, il Phone number ID del numero
    | business e un template già approvato (hello_world va bene). Poi:
    | php artisan migrate && php artisan whatsapp:prova
    |
    | Webhook da registrare su Meta: {APP_URL}/whatsapp/webhook
    | Verifica: WHATSAPP_VERIFY_TOKEN. Firma degli stati: WHATSAPP_APP_SECRET.
    */
    'enabled' => (bool) env('WHATSAPP_ENABLED', false),

    'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),

    'token' => env('WHATSAPP_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    'app_secret' => env('WHATSAPP_APP_SECRET'),

    'template_prova' => env('WHATSAPP_TEST_TEMPLATE', 'hello_world'),

    'lingua_prova' => env('WHATSAPP_TEST_TEMPLATE_LANGUAGE', 'en_US'),

    'destinatario_prova' => env('WHATSAPP_TEST_TO'),

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),

];

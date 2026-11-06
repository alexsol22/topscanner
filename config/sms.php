<?php

return [

    /*
    | Gli SMS sono disattivati finché SMS_ENABLED non è true: nessuna chiamata al provider
    | e nessuna riga scritta in tc_send_sms.
    */
    'enabled' => (bool) env('SMS_ENABLED', false),

    'api_url' => env('SMS_API_URL', 'https://sms.smsop.it/index.php/api'),

    'username' => env('SMS_USERNAME'),

    'password' => env('SMS_PASSWORD'),

    'sender' => env('SMS_SENDER', 'S-420'),

    /* Tipo di invio inviato al provider (sms_type). */
    'type' => env('SMS_TYPE', 'P'),

    /* Password richiesta al callback di consegna (parametro "psw"). */
    'delivery_password' => env('SMS_DELIVERY_PASSWORD'),

    'timeout' => (int) env('SMS_TIMEOUT', 15),

];

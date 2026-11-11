<?php

return [

    /*
    | Con true lo scheduler esegue i cron delle notifiche in prova sicura:
    | solo l'operatore alesolax55@gmail.com e il cliente «prova», mail solo a lui.
    */

    'prova' => filter_var(env('NOTIFICHE_PROVA', true), FILTER_VALIDATE_BOOLEAN),

];

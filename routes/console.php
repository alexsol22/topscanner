<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Flag senza valore: ['--prova-sicura' => true] diventa --prova-sicura=1 e Symfony rifiuta l'opzione.
$prova = config('notifiche.prova') ? ['--prova-sicura'] : [];

Schedule::command('notifiche:avviso-turni', $prova)->hourly()->withoutOverlapping();
Schedule::command('notifiche:ritardi', $prova)->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifiche:scadenze', $prova)->dailyAt('08:00')->withoutOverlapping();
Schedule::command('notifiche:programmazione', $prova)->weeklyOn(1, '07:00')->withoutOverlapping();

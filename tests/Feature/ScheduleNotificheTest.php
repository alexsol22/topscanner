<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ScheduleNotificheTest extends TestCase
{
    public function test_con_prova_disattiva_lo_scheduler_non_passa_il_flag(): void
    {
        $comandi = collect(app(Schedule::class)->events())->map(fn ($evento) => $evento->command)->implode("\n");

        $this->assertStringContainsString('notifiche:ritardi', $comandi);
        $this->assertStringNotContainsString('--prova-sicura', $comandi);
    }

    public function test_con_prova_attiva_lo_scheduler_passa_il_flag_senza_valore(): void
    {
        $ambiente = getenv();
        $ambiente['NOTIFICHE_PROVA'] = 'true';

        $processo = new Process([PHP_BINARY, 'artisan', 'schedule:list'], base_path(), $ambiente);
        $processo->mustRun();

        $elenco = $processo->getOutput();

        $this->assertMatchesRegularExpression('/notifiche:ritardi\s+--prova-sicura(\s|$)/', $elenco);
        $this->assertDoesNotMatchRegularExpression('/--prova-sicura[=\'"0-9]/', $elenco);
    }
}

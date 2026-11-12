<?php

namespace Tests\Feature;

use Illuminate\Console\Application;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\ScheduledTaskSkipReason;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Mockery;
use Monolog\Handler\NullHandler;
use RuntimeException;
use Tests\TestCase;

class RegistraEsecuzioneCronTest extends TestCase
{
    /** @var list<MessageLogged> */
    private array $diario = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.channels.cron' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]]);
        Log::forgetChannel('cron');

        Log::listen(function (MessageLogged $messaggio): void {
            if (($messaggio->context['comando'] ?? null) === 'notifiche:ritardi') {
                $this->diario[] = $messaggio;
            }
        });
    }

    public function test_un_cron_avviato_e_completato_finisce_nel_diario(): void
    {
        $task = $this->task('notifiche:ritardi');
        $task->exitCode = 0;

        event(new ScheduledTaskStarting($task));
        event(new ScheduledTaskFinished($task, 1.25));

        $this->assertSame([
            'comando' => 'notifiche:ritardi',
            'riga' => $task->command,
        ], $this->voce('Cron avviato')->context);
        $this->assertSame([
            'comando' => 'notifiche:ritardi',
            'riga' => $task->command,
            'secondi' => 1.25,
            'uscita' => 0,
        ], $this->voce('Cron completato')->context);
        $this->assertSame('info', $this->voce('Cron completato')->level);
    }

    public function test_un_cron_con_uscita_diversa_da_zero_resta_come_errore(): void
    {
        $task = $this->task('notifiche:ritardi');
        $task->exitCode = 1;

        event(new ScheduledTaskFinished($task, 0.4));
        event(new ScheduledTaskFailed($task, new RuntimeException('connessione rifiutata'), 0.4));

        $this->assertNull($this->voce('Cron completato', obbligatorio: false));
        $errore = $this->voce('Cron fallito');
        $this->assertSame('error', $errore->level);
        $this->assertSame(1, $errore->context['uscita']);
        $this->assertSame(0.4, $errore->context['secondi']);
        $this->assertSame('connessione rifiutata', $errore->context['errore']);
    }

    public function test_un_cron_sovrapposto_non_viene_registrato_come_errore(): void
    {
        $task = $this->task('notifiche:ritardi');
        $task->skippedBecauseOverlapping = true;

        event(new ScheduledTaskFinished($task, 0.01));
        event(new ScheduledTaskFailed($task, new RuntimeException('exit code'), 0.01));

        $this->assertSame('warning', $this->voce('Cron saltato: esecuzione precedente ancora in corso')->level);
        $this->assertNull($this->voce('Cron fallito', obbligatorio: false));
    }

    public function test_un_cron_in_pausa_viene_registrato_come_salto(): void
    {
        event(new ScheduledTaskSkipped($this->task('notifiche:ritardi'), ScheduledTaskSkipReason::Paused));

        $voce = $this->voce('Cron saltato');
        $this->assertSame('warning', $voce->level);
        $this->assertSame('paused', $voce->context['motivo']);
        $this->assertSame('notifiche:ritardi', $voce->context['comando']);
    }

    private function task(string $comando): Event
    {
        return new Event(Mockery::mock(EventMutex::class), Application::formatCommandString($comando));
    }

    private function voce(string $messaggio, bool $obbligatorio = true): ?MessageLogged
    {
        foreach ($this->diario as $voce) {
            if ($voce->message === $messaggio) {
                return $voce;
            }
        }

        if ($obbligatorio) {
            $this->fail('Voce di diario assente: '.$messaggio);
        }

        return null;
    }
}

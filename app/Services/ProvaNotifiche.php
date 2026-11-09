<?php

namespace App\Services;

use App\Models\Legacy\Admin;
use RuntimeException;

class ProvaNotifiche
{
    public const EMAIL = 'alesolax55@gmail.com';

    public function attiva(Comunicazioni $comunicazioni): int
    {
        $operatori = Admin::query()->whereRaw('LOWER(email) = ?', [self::EMAIL])->limit(2)->get();

        if ($operatori->count() !== 1) {
            throw new RuntimeException('Prova interrotta: deve esistere esattamente un operatore con l’indirizzo di test.');
        }

        $comunicazioni->attivaProva(self::EMAIL);

        return (int) $operatori->first()->id;
    }
}

<?php

namespace App\Models\Legacy;

use App\Casts\YesNo;

class Messaggio extends LegacyModel
{
    protected $table = 'tc_messaggi';

    protected function casts(): array
    {
        return [
            'invia_sms' => YesNo::class,
            'invia_email' => YesNo::class,
            'segnato_fino_al' => 'date',
        ];
    }
}

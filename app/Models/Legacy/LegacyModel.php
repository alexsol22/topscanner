<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

abstract class LegacyModel extends Model
{
    protected $connection = 'mysql';

    public $timestamps = false;

    protected $guarded = [];

    /** In molte tabelle del dump la colonna id non ha AUTO_INCREMENT: l'id viene assegnato qui, senza modificare lo schema. */
    protected static function booted(): void
    {
        static::creating(function (self $modello) {
            if ($modello->getKeyName() === 'id' && ! $modello->getKey()) {
                $modello->incrementing = false;
                $modello->id = (int) static::query()->max('id') + 1;
            }
        });
    }
}

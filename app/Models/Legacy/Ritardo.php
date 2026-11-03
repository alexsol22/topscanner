<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ritardo extends LegacyModel
{
    protected $table = 'tc_ritardi';

    public $incrementing = false;

    /** Nel dump la colonna id non ha AUTO_INCREMENT: l'id viene assegnato qui, senza modificare lo schema. */
    protected static function booted(): void
    {
        static::creating(function (self $ritardo) {
            if (! $ritardo->getKey()) {
                $ritardo->id = (int) static::query()->max('id') + 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'data_ritardo_inizio' => 'datetime',
            'data_ritardo_fine' => 'datetime',
        ];
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno');
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'id_operatore');
    }
}

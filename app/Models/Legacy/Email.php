<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Email extends LegacyModel
{
    protected $table = 'tc_email';

    public $incrementing = false;

    /** Nel dump la colonna id non ha AUTO_INCREMENT: l'id viene assegnato qui, senza modificare lo schema. */
    protected static function booted(): void
    {
        static::creating(function (self $email) {
            if (! $email->getKey()) {
                $email->id = (int) static::query()->max('id') + 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'data_invio' => 'datetime',
        ];
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'id_operatore');
    }
}

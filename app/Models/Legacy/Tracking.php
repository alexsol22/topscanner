<?php

namespace App\Models\Legacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tracking extends LegacyModel
{
    protected $table = '_tracking';

    public $incrementing = false;

    /** Nel dump la colonna id non ha AUTO_INCREMENT: l'id viene assegnato qui, senza modificare lo schema. */
    protected static function booted(): void
    {
        static::creating(function (self $tracking) {
            if (! $tracking->getKey()) {
                $tracking->id = (int) static::query()->max('id') + 1;
            }
        });
    }

    /** Equivale a MyUtils::setTracking di Yii. */
    public static function registra(string $azione): void
    {
        static::create([
            'id_user' => auth()->id(),
            'addr' => (string) request()->ip(),
            'host' => '',
            'agent' => substr((string) request()->userAgent(), 0, 255),
            'pagina' => '',
            'data_view' => now()->format('Y-m-d H:i'),
            'action' => $azione,
        ]);
    }

    protected function casts(): array
    {
        return [
            'data_view' => 'datetime',
        ];
    }

    public function utente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}

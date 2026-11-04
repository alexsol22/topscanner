<?php

namespace App\Models;

use App\Casts\YesNo;
use App\Enums\Ruolo;
use App\Models\Legacy\Cliente as LegacyCliente;
use App\Models\Legacy\Turno as LegacyTurno;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Utente applicativo su tc_admin (DB legacy Yii). user_type: 1 super, 2 admin, 3 cliente, 4 operatore.
 */
class User extends Authenticatable
{
    protected $connection = 'mysql';

    protected $table = 'tc_admin';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['password', 'password_hash'];

    protected function casts(): array
    {
        return [
            'attivo' => YesNo::class,
            'password' => 'hashed',
            'last_login' => 'datetime',
            'costo_orario' => 'float',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->user ??= $user->email;
        });
    }

    protected function ruolo(): Attribute
    {
        return Attribute::make(
            get: fn () => match ((int) $this->user_type) {
                1 => Ruolo::SuperAdmin,
                2 => Ruolo::Admin,
                3 => Ruolo::Cliente,
                default => Ruolo::Operatore,
            },
            set: fn (Ruolo|string $valore) => ['user_type' => match ($valore instanceof Ruolo ? $valore : Ruolo::from($valore)) {
                Ruolo::SuperAdmin => 1,
                Ruolo::Admin => 2,
                Ruolo::Cliente => 3,
                Ruolo::Operatore => 4,
            }],
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->nome, set: fn ($v) => ['nome' => $v]);
    }

    protected function clienteId(): Attribute
    {
        return Attribute::make(get: fn () => $this->id_cliente, set: fn ($v) => ['id_cliente' => $v]);
    }

    protected function fotoPath(): Attribute
    {
        return Attribute::make(get: fn () => $this->avatar, set: fn ($v) => ['avatar' => $v]);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(LegacyCliente::class, 'id_cliente');
    }

    public function turni(): HasMany
    {
        return $this->hasMany(LegacyTurno::class, 'id_operatore');
    }

    public function getNomeCompletoAttribute(): string
    {
        return trim("{$this->nome} {$this->cognome}");
    }

    public function isStaff(): bool
    {
        return in_array($this->ruolo, [Ruolo::SuperAdmin, Ruolo::Admin], true);
    }

    public function isCliente(): bool
    {
        return $this->ruolo === Ruolo::Cliente;
    }

    public function isOperatore(): bool
    {
        return $this->ruolo === Ruolo::Operatore;
    }
}

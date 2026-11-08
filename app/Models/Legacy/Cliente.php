<?php

namespace App\Models\Legacy;

use App\Casts\YesNo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends LegacyModel
{
    protected $table = 'tc_clienti';

    protected function casts(): array
    {
        return [
            'accesso' => YesNo::class,
            'inizio_contratto' => 'date',
            'fine_contratto' => 'date',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function comune(): BelongsTo
    {
        return $this->belongsTo(Comune::class, 'citta_id');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function sedi(): HasMany
    {
        return $this->hasMany(Sede::class, 'id_cliente');
    }

    public function postazioni(): HasMany
    {
        return $this->hasMany(Postazione::class, 'id_cliente');
    }

    public function turni(): HasMany
    {
        return $this->hasMany(Turno::class, 'id_cliente');
    }

    public function presenze(): HasMany
    {
        return $this->hasMany(Gda::class, 'id_cliente');
    }

    public function operatori(): HasMany
    {
        return $this->hasMany(Admin::class, 'id_cliente');
    }

    public function operatoriPreferiti(): HasMany
    {
        return $this->hasMany(OperatorePreferito::class, 'id_cliente');
    }

    public function operatoriEsclusi(): HasMany
    {
        return $this->hasMany(OperatoreEscludi::class, 'id_cliente');
    }

    /** Come Yii: super (tipo 1) solo clienti di test, cliente (tipo 3) solo se stesso, gli altri tutti tranne i test. */
    public function scopeVisibiliA(Builder $q, User $user): Builder
    {
        return match (true) {
            $user->isCliente() => $q->whereKey($user->id_cliente),
            (int) $user->user_type === 1 => $q->where('isTest', 1),
            $user->isStaff() => $q->where(fn ($w) => $w->whereNull('isTest')->orWhere('isTest', '!=', 1)),
            default => $q->whereRaw('1 = 0'),
        };
    }

    protected function citta(): Attribute
    {
        return Attribute::get(fn () => $this->comune?->comune);
    }

    protected function provinciaSigla(): Attribute
    {
        return Attribute::get(fn () => $this->getRelationValue('provincia')?->sigla);
    }
}

<?php

namespace App\Models\Legacy;

use App\Casts\YesNo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends LegacyModel
{
    protected $table = 'tc_admin';

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

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function provinciaRel(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'provincia');
    }

    public function turni(): HasMany
    {
        return $this->hasMany(Turno::class, 'id_operatore');
    }

    public function presenze(): HasMany
    {
        return $this->hasMany(Gda::class, 'id_operatore');
    }

    public function ritardi(): HasMany
    {
        return $this->hasMany(Ritardo::class, 'id_operatore');
    }

    public function materiali(): HasMany
    {
        return $this->hasMany(Materiale::class, 'id_operatore');
    }

    public function bacheche(): HasMany
    {
        return $this->hasMany(Bacheca::class, 'id_operatore');
    }

    public function bachecheViste(): HasMany
    {
        return $this->hasMany(BachecaView::class, 'id_operatore');
    }

    public function documentiVisti(): HasMany
    {
        return $this->hasMany(DocumentoView::class, 'id_operatore');
    }

    public function clientiEsclusi(): HasMany
    {
        return $this->hasMany(OperatoreEscludi::class, 'id_operatore');
    }

    public function clientiPreferiti(): HasMany
    {
        return $this->hasMany(OperatorePreferito::class, 'id_operatore');
    }

    protected function nomeCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nome} {$this->cognome}"));
    }
}

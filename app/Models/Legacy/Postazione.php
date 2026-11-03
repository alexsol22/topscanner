<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postazione extends LegacyModel
{
    protected $table = 'tc_postazioni';

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede');
    }

    public function codice(): BelongsTo
    {
        return $this->belongsTo(Codice::class, 'id_codice');
    }

    public function presenze(): HasMany
    {
        return $this->hasMany(Gda::class, 'id_postazione');
    }

    public function cartella(): string
    {
        return trim(config('filesystems.private_paths.documenti'), '/').'/Cliente_'.$this->id_cliente.'/Sede_'.$this->id_sede.'/Postazione_'.$this->id;
    }

    public function percorsoFile(): ?string
    {
        return $this->file ? $this->cartella().'/'.basename($this->file) : null;
    }

    protected function minutiPassaggio(): Attribute
    {
        return Attribute::get(fn () => $this->minuti);
    }
}

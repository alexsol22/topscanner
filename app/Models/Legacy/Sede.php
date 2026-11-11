<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Sede extends LegacyModel
{
    protected $table = 'tc_sedi';

    protected function casts(): array
    {
        return [
            'inizio_contratto' => 'date',
            'fine_contratto' => 'date',
            'lat' => 'float',
            'lng' => 'float',
            'tariffa' => 'float',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function codice(): BelongsTo
    {
        return $this->belongsTo(Codice::class, 'id_codice');
    }

    public function comune(): BelongsTo
    {
        return $this->belongsTo(Comune::class, 'citta_id');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function postazioni(): HasMany
    {
        return $this->hasMany(Postazione::class, 'id_sede');
    }

    public function turni(): HasMany
    {
        return $this->hasMany(Turno::class, 'id_sede');
    }

    public function presenze(): HasMany
    {
        return $this->hasMany(Gda::class, 'id_sede');
    }

    /** Cartella dei file su disco privato, mantenendo il percorso legacy. */
    public function cartella(): string
    {
        return trim(config('filesystems.private_paths.documenti'), '/').'/Cliente_'.$this->id_cliente.'/Sede_'.$this->id;
    }

    public function percorsoFoto(): ?string
    {
        return $this->foto ? $this->cartella().'/'.basename($this->foto) : null;
    }

    /** Come getImageSede: il file indicato sulla sede, altrimenti quello della prima postazione. */
    public function percorsoImmagineTimbratura(): ?string
    {
        $disco = Storage::disk(config('filesystems.files_disk'));

        if (filled($this->foto)) {
            $percorso = $this->percorsoFoto();

            return $percorso && $disco->exists($percorso) ? $percorso : null;
        }

        $postazione = $this->postazioni()
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->orderBy('id')
            ->first();
        $percorso = $postazione?->percorsoFile();

        return $percorso && $disco->exists($percorso) ? $percorso : null;
    }

    public function haImmagineTimbratura(): bool
    {
        return $this->percorsoImmagineTimbratura() !== null;
    }

    protected function citta(): Attribute
    {
        return Attribute::get(fn () => $this->comune?->comune);
    }

    protected function provinciaSigla(): Attribute
    {
        return Attribute::get(fn () => $this->getRelationValue('provincia')?->sigla);
    }

    protected function distanzaMax(): Attribute
    {
        return Attribute::get(fn () => $this->distanza);
    }

    /** @return array<int, int> ore richieste per giorno (1 = lunedì) dalle colonne lun..dom */
    protected function oreRichieste(): Attribute
    {
        return Attribute::get(fn () => [1 => (int) $this->lun, (int) $this->mar, (int) $this->mer, (int) $this->gio, (int) $this->ven, (int) $this->sab, (int) $this->dom]);
    }
}

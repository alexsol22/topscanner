<?php

namespace App\Models\Legacy;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turno extends LegacyModel
{
    protected $table = 'tc_turni';

    protected function casts(): array
    {
        return [
            'ore' => 'float',
            'ore_lavorate' => 'float',
            'ore_new' => 'float',
            'data_inizio' => 'date',
            'data_fine' => 'date',
        ];
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'id_operatore');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede');
    }

    public function presenze(): HasMany
    {
        return $this->hasMany(Gda::class, 'id_turno');
    }

    public function ritardi(): HasMany
    {
        return $this->hasMany(Ritardo::class, 'id_turno');
    }

    protected function inizio(): Attribute
    {
        return Attribute::get(fn () => $this->istante($this->data_inizio, $this->ora_inizio));
    }

    protected function fine(): Attribute
    {
        return Attribute::get(fn () => $this->istante($this->data_fine, $this->ora_fine));
    }

    private function istante(?Carbon $data, ?string $ora): ?Carbon
    {
        return $data?->copy()->setTimeFromTimeString($ora ?: '00:00');
    }

    protected function note(): Attribute
    {
        return Attribute::get(fn () => $this->note_operatore);
    }

    /** Stato derivato mostrato in calendario (stessa logica dei colori di Yii `_getColore`). */
    public function statoEffettivo(): string
    {
        $now = now();
        $presenze = $this->relationLoaded('presenze') ? $this->presenze : collect();
        $ingressoRegistrato = $presenze->contains('tipo', Gda::INIZIO);
        $uscitaRegistrata = $presenze->contains('tipo', Gda::FINE);

        return match (true) {
            $this->stato === 'F' || ($ingressoRegistrato && $uscitaRegistrata) => 'completato',
            ($this->stato === 'I' || $ingressoRegistrato) && $this->fine->lt($now) => 'non_completato',
            $this->stato === 'I' || $ingressoRegistrato => 'in_corso',
            $this->inizio->lt($now) && $this->fine->lt($now) => 'non_eseguito',
            $this->inizio->lt($now) => 'non_timbrato',
            default => 'programmato',
        };
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function etichette(): array
    {
        return [
            'programmato' => ['Programmato', 'bg-sky-100 text-sky-700'],
            'in_corso' => ['In corso', 'bg-emerald-100 text-emerald-700'],
            'completato' => ['Completato', 'bg-teal-100 text-teal-700'],
            'non_timbrato' => ['Inizio non timbrato', 'bg-amber-100 text-amber-700'],
            'non_completato' => ['Non completato', 'bg-orange-100 text-orange-700'],
            'non_eseguito' => ['Non eseguito', 'bg-rose-100 text-rose-700'],
        ];
    }
}

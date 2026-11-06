<?php

namespace App\Livewire;

use App\Models\Legacy\BachecaView;
use App\Models\Legacy\DocumentoView;
use Livewire\Component;

/** Badge della sidebar: avvisi di bacheca non letti o documenti non ancora visti dall'operatore. */
class ContatoreNotifiche extends Component
{
    public string $tipo = 'bacheca';

    public function conteggio(): int
    {
        $utente = auth()->user();

        if (! $utente?->isOperatore()) {
            return 0;
        }

        $vista = $this->tipo === 'documenti' ? DocumentoView::query() : BachecaView::query();

        return $vista->where('id_operatore', $utente->id)
            ->where(fn ($q) => $q->whereNull('visto')->orWhere('visto', 'N'))
            ->count();
    }

    public function render()
    {
        return view('livewire.contatore-notifiche', ['conteggio' => $this->conteggio()]);
    }
}

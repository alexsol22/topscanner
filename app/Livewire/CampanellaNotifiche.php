<?php

namespace App\Livewire;

use App\Models\Legacy\BachecaView;
use App\Models\Legacy\DocumentoView;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/** Campanella nella barra in alto: avvisi di bacheca e documenti non ancora letti dall'operatore. */
class CampanellaNotifiche extends Component
{
    public function segnaLetto(int $idVista): void
    {
        $this->nonLetti(BachecaView::query())->whereKey($idVista)->update(['visto' => 'Y', 'data_vista' => now()]);
    }

    public function segnaTuttiLetti(): void
    {
        $this->nonLetti(BachecaView::query())->update(['visto' => 'Y', 'data_vista' => now()]);
    }

    private function nonLetti(Builder $query): Builder
    {
        return $query->where('id_operatore', auth()->id())
            ->where(fn ($q) => $q->whereNull('visto')->orWhere('visto', 'N'));
    }

    public function render()
    {
        if (! auth()->user()?->isOperatore()) {
            return view('livewire.campanella-notifiche', ['avvisi' => collect(), 'documenti' => collect(), 'totale' => 0]);
        }

        $avvisi = $this->nonLetti(BachecaView::query())->with('bacheca')->orderByDesc('id')->limit(10)->get()
            ->filter(fn ($vista) => $vista->bacheca);
        $documenti = $this->nonLetti(DocumentoView::query())->with('documento')->orderByDesc('id')->limit(10)->get()
            ->filter(fn ($vista) => $vista->documento);

        return view('livewire.campanella-notifiche', [
            'avvisi' => $avvisi,
            'documenti' => $documenti,
            'totale' => $this->nonLetti(BachecaView::query())->count() + $this->nonLetti(DocumentoView::query())->count(),
        ]);
    }
}

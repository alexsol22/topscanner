<?php

namespace App\Livewire;

use App\Models\Legacy;
use App\Models\Legacy\Admin;
use App\Models\Legacy\BachecaView;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Bacheca')]
class Bacheca extends Component
{
    public bool $modal = false;

    public array $f = [];

    public array $destinatari = [];

    public function mount(): void
    {
        abort_if(auth()->user()->isCliente(), 403);
    }

    public function nuovo(): void
    {
        abort_unless(auth()->user()->isStaff(), 403);
        $this->f = ['titolo' => '', 'testo' => '', 'per_tutti' => true];
        $this->destinatari = [];
        $this->resetValidation();
        $this->modal = true;
    }

    public function salva(): void
    {
        abort_unless(auth()->user()->isStaff(), 403);
        $this->validate([
            'f.titolo' => 'required|string|max:150',
            'f.testo' => 'required|string|max:5000',
            'destinatari' => 'array',
            'destinatari.*' => 'exists:tc_admin,id',
        ]);
        if (! $this->f['per_tutti'] && ! $this->destinatari) {
            $this->addError('destinatari', 'Seleziona almeno un destinatario.');

            return;
        }

        $avviso = Legacy\Bacheca::create([
            'titolo' => $this->f['titolo'], 'testo' => $this->f['testo'],
            'tutti' => $this->f['per_tutti'] ? 'Y' : 'N', 'data_invio' => today(),
        ]);
        $ids = $this->f['per_tutti'] ? $this->operatoriAttivi()->pluck('id') : collect($this->destinatari);
        $avviso->update(['quanti' => $ids->count()]);
        foreach ($ids as $id) {
            BachecaView::create(['id_bacheca' => $avviso->id, 'id_operatore' => $id]);
        }

        $this->modal = false;
    }

    public function segnaLetto(int $id): void
    {
        BachecaView::where('id_bacheca', $id)->where('id_operatore', auth()->id())
            ->where(fn ($q) => $q->whereNull('visto')->orWhere('visto', 'N'))
            ->update(['visto' => 'Y', 'data_vista' => now()]);
    }

    public function elimina(int $id): void
    {
        abort_unless(auth()->user()->isStaff(), 403);
        BachecaView::where('id_bacheca', $id)->delete();
        Legacy\Bacheca::findOrFail($id)->delete();
    }

    private function operatoriAttivi()
    {
        return Admin::where('user_type', 4)->where('attivo', 'Y');
    }

    public function render()
    {
        $u = auth()->user();
        if ($u->isOperatore()) {
            $viste = BachecaView::where('id_operatore', $u->id)->get()->keyBy('id_bacheca');
            $avvisi = Legacy\Bacheca::whereIn('id', $viste->keys())->orderByDesc('data_invio')->orderByDesc('id')->get()
                ->each(fn ($a) => $a->letto = (bool) $viste[$a->id]->visto);
        } else {
            $avvisi = Legacy\Bacheca::withCount([
                'visualizzazioni',
                'visualizzazioni as letti_count' => fn ($q) => $q->where('visto', 'Y'),
            ])->orderByDesc('data_invio')->orderByDesc('id')->get();
        }

        return view('livewire.bacheca', [
            'avvisi' => $avvisi,
            'operatori' => $u->isStaff() ? $this->operatoriAttivi()->orderBy('cognome')->get() : collect(),
        ]);
    }
}

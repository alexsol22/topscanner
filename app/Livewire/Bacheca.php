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

    public ?int $modificaId = null;

    public ?int $vistiId = null;

    public function mount(): void
    {
        abort_if(auth()->user()->isCliente(), 403);
    }

    public function nuovo(): void
    {
        abort_unless(auth()->user()->isStaff(), 403);
        $this->modificaId = null;
        $this->f = ['titolo' => '', 'testo' => '', 'per_tutti' => true];
        $this->destinatari = [];
        $this->resetValidation();
        $this->modal = true;
    }

    public function modifica(int $id): void
    {
        abort_unless(auth()->user()->isStaff(), 403);
        $avviso = Legacy\Bacheca::findOrFail($id);

        $this->modificaId = $avviso->id;
        $this->f = ['titolo' => $avviso->titolo, 'testo' => $avviso->testo, 'per_tutti' => (bool) $avviso->tutti];
        $this->destinatari = $avviso->tutti ? [] : BachecaView::where('id_bacheca', $id)->pluck('id_operatore')->map(fn ($v) => (string) $v)->all();
        $this->resetValidation();
        $this->modal = true;
    }

    public function mostraVisti(int $id): void
    {
        abort_unless(auth()->user()->isStaff(), 403);
        $this->vistiId = Legacy\Bacheca::findOrFail($id)->id;
    }

    public function chiudiVisti(): void
    {
        $this->vistiId = null;
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

        $dati = ['titolo' => $this->f['titolo'], 'testo' => $this->f['testo'], 'tutti' => $this->f['per_tutti'] ? 'Y' : 'N'];

        if ($this->modificaId) {
            $avviso = Legacy\Bacheca::findOrFail($this->modificaId);
            $avviso->update($dati);
        } else {
            $avviso = Legacy\Bacheca::create($dati + ['data_invio' => today()]);
        }

        $ids = $this->f['per_tutti'] ? $this->operatoriAttivi()->pluck('id') : collect($this->destinatari);
        $this->allineaVisualizzazioni($avviso, $ids);

        $this->modal = false;
        $this->modificaId = null;
    }

    /** Come Bacheca::addViews: aggiunge le visualizzazioni mancanti e toglie quelle degli operatori non più destinatari. */
    private function allineaVisualizzazioni(Legacy\Bacheca $avviso, $ids): void
    {
        $ids = $ids->map(fn ($id) => (int) $id)->unique()->values();
        $avviso->update(['quanti' => $ids->count()]);

        if ($ids->isEmpty()) {
            return;
        }

        $esistenti = BachecaView::where('id_bacheca', $avviso->id)->pluck('id_operatore')->map(fn ($id) => (int) $id);

        foreach ($ids->diff($esistenti) as $id) {
            BachecaView::create(['id_bacheca' => $avviso->id, 'id_operatore' => $id]);
        }

        BachecaView::where('id_bacheca', $avviso->id)->whereNotIn('id_operatore', $ids)->delete();
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
            'vistiElenco' => $this->vistiId && $u->isStaff()
                ? BachecaView::with('operatore')->where('id_bacheca', $this->vistiId)->get()
                : collect(),
            'avvisi' => $avvisi,
            'operatori' => $u->isStaff() ? $this->operatoriAttivi()->orderBy('cognome')->get() : collect(),
        ]);
    }
}

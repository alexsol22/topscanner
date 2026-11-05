<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Gda;
use App\Models\Legacy\Turno;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Presenze (GDA)')]
class Presenze extends Component
{
    use ConAccesso, WithPagination;

    public string $da = '';

    public string $a = '';

    public ?int $filtroSede = null;

    public ?int $filtroOperatore = null;

    public function mount(): void
    {
        $this->da = now()->startOfMonth()->toDateString();
        $this->a = now()->toDateString();
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $clienti = $this->clientiVisibili()->select('tc_clienti.id');
        $filtra = fn ($q, $campo) => $q
            ->whereIn('id_cliente', $clienti)
            ->when($this->da, fn ($q) => $q->where($campo, '>=', Carbon::parse($this->da)->toDateString()))
            ->when($this->a, fn ($q) => $q->where($campo, '<=', Carbon::parse($this->a)->toDateString()))
            ->when($this->filtroSede, fn ($q) => $q->where('id_sede', $this->filtroSede))
            ->when($this->filtroOperatore, fn ($q) => $q->where('id_operatore', $this->filtroOperatore));

        $turni = $filtra(Turno::query(), 'data_inizio');
        $gruppi = $filtra(Turno::query(), 'data_inizio')
            ->selectRaw('id_operatore, SUM(ore) as previste, SUM(ore_lavorate) as lavorate')
            ->groupBy('id_operatore')
            ->get();
        $previste = (float) $gruppi->sum('previste');
        $lavorate = (float) $gruppi->sum('lavorate');

        $operatoriGruppi = Admin::whereIn('id', $gruppi->pluck('id_operatore')->filter())->get()->keyBy('id');
        $perOperatore = $gruppi->mapWithKeys(fn ($g) => [$g->id_operatore => [
            'nome' => $operatoriGruppi->get($g->id_operatore)?->nome_completo ?? '—', 'previste' => (float) $g->previste, 'lavorate' => (float) $g->lavorate,
        ]])->sortBy('nome');

        return view('livewire.presenze', [
            'previste' => $previste, 'lavorate' => $lavorate, 'perOperatore' => $perOperatore,
            'righe' => $filtra(Gda::query(), 'data')->with(['operatore', 'sede', 'postazione'])->orderByDesc('data')->orderByDesc('orario')->paginate(15),
            'sedi' => $this->sediVisibili()->orderBy('descrizione')->get(['id', 'descrizione']),
            'operatori' => Admin::where('user_type', 4)->orderBy('cognome')->get(),
        ]);
    }
}

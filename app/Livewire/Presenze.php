<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
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

    public ?int $filtroCliente = null;

    public string $vista = 'clienti';

    public ?int $passaggiTurnoId = null;

    public function mount(): void
    {
        $this->da = now()->startOfMonth()->toDateString();
        $this->a = now()->toDateString();
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function updatedVista(): void
    {
        $this->filtroCliente = null;
        $this->filtroOperatore = null;
    }

    public function mostraPassaggi(int $turno): void
    {
        $this->passaggiTurnoId = $turno;
    }

    public function chiudiPassaggi(): void
    {
        $this->passaggiTurnoId = null;
    }

    public function render()
    {
        $clienti = $this->clientiVisibili()->select('tc_clienti.id');
        $filtra = fn ($q, $campo) => $q
            ->whereIn('id_cliente', $clienti)
            ->when($this->da, fn ($q) => $q->where($campo, '>=', Carbon::parse($this->da)->toDateString()))
            ->when($this->a, fn ($q) => $q->where($campo, '<=', Carbon::parse($this->a)->toDateString()))
            ->when($this->filtroSede, fn ($q) => $q->where('id_sede', $this->filtroSede))
            ->when($this->filtroCliente, fn ($q) => $q->where('id_cliente', $this->filtroCliente))
            ->when($this->filtroOperatore, fn ($q) => $q->where('id_operatore', $this->filtroOperatore));

        $gruppi = $filtra(Turno::query(), 'data_inizio')
            ->selectRaw('id_operatore, SUM(ore) as previste, SUM(ore_lavorate) as lavorate')
            ->groupBy('id_operatore')
            ->get();

        $operatoriGruppi = Admin::whereIn('id', $gruppi->pluck('id_operatore')->filter())->get()->keyBy('id');
        $perOperatore = $gruppi->mapWithKeys(fn ($g) => [$g->id_operatore => [
            'nome' => $operatoriGruppi->get($g->id_operatore)?->nome_completo ?? '—', 'previste' => (float) $g->previste, 'lavorate' => (float) $g->lavorate,
        ]])->sortBy('nome');

        $perCliente = $filtra(Turno::query(), 'data_inizio')
            ->selectRaw('id_cliente, SUM(ore) as previste, SUM(ore_lavorate) as lavorate')
            ->groupBy('id_cliente')
            ->havingRaw('SUM(ore) > 0')
            ->get();
        $nomiClienti = Cliente::whereIn('id', $perCliente->pluck('id_cliente'))->pluck('ragione_sociale', 'id');
        $perCliente = $perCliente->map(fn ($g) => [
            'nome' => $nomiClienti->get($g->id_cliente, '—'), 'previste' => (float) $g->previste, 'lavorate' => (float) $g->lavorate,
        ])->sortBy('nome');

        $turniGda = $filtra(Turno::query(), 'data_inizio')
            ->where('stato', 'F')
            ->with(['cliente', 'sede', 'operatore', 'presenze.postazione'])
            ->orderByDesc('data_inizio')->orderByDesc('ora_inizio')
            ->paginate(25, pageName: 'gda');

        return view('livewire.presenze', [
            'perCliente' => $perCliente,
            'totali' => $this->totali(),
            'perOperatore' => $perOperatore,
            'turniGda' => $turniGda,
            'turnoPassaggi' => $this->passaggiTurnoId ? Turno::with(['sede', 'presenze.postazione'])->find($this->passaggiTurnoId) : null,
            'righe' => $filtra(Gda::query(), 'data')->with(['operatore', 'sede', 'postazione'])->orderByDesc('data')->orderByDesc('orario')->paginate(15),
            'sedi' => $this->sediVisibili()->orderBy('descrizione')->get(['id', 'descrizione']),
            'clientiElenco' => $this->clientiVisibili()->orderBy('ragione_sociale')->get(['tc_clienti.id', 'ragione_sociale']),
            'operatori' => Admin::where('user_type', 4)->orderBy('cognome')->get(),
        ]);
    }

    /**
     * Come gda::getTotali: solo turni completati e, a differenza dei filtri dell'elenco, un solo criterio
     * con priorità cliente, sede, operatore; senza filtri non c'è totale.
     *
     * @return array{etichetta: string, previste: float, lavorate: float, saldo: float}|null
     */
    private function totali(): ?array
    {
        [$etichetta, $colonna, $valore] = match (true) {
            (bool) $this->filtroCliente => ['cliente', 'id_cliente', $this->filtroCliente],
            (bool) $this->filtroSede => ['sede', 'id_sede', $this->filtroSede],
            (bool) $this->filtroOperatore => ['operatore', 'id_operatore', $this->filtroOperatore],
            default => [null, null, null],
        };

        if (! $etichetta) {
            return null;
        }

        $righe = Turno::query()
            ->whereIn('id_cliente', $this->clientiVisibili()->select('tc_clienti.id'))
            ->where('stato', 'F')
            ->where($colonna, $valore)
            ->when($this->da, fn ($q) => $q->where('data_inizio', '>=', Carbon::parse($this->da)->toDateString()))
            ->when($this->a, fn ($q) => $q->where('data_inizio', '<=', Carbon::parse($this->a)->toDateString()))
            ->selectRaw('SUM(ore) as previste, SUM(ore_lavorate) as lavorate')
            ->first();

        $previste = (float) $righe->previste;
        $lavorate = (float) $righe->lavorate;

        return ['etichetta' => $etichetta, 'previste' => $previste, 'lavorate' => $lavorate, 'saldo' => $lavorate - $previste];
    }
}

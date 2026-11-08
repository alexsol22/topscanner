<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Turno;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Giornale delle attività')]
class Presenze extends Component
{
    use ConAccesso, WithPagination;

    #[Url]
    public string $da = '';

    #[Url]
    public string $a = '';

    #[Url(as: 'sede')]
    public ?int $filtroSede = null;

    #[Url(as: 'operatore')]
    public ?int $filtroOperatore = null;

    #[Url(as: 'cliente')]
    public ?int $filtroCliente = null;

    public string $vista = 'gda';

    public ?int $passaggiTurnoId = null;

    public function mount(): void
    {
        $this->vista = match (request()->route()?->getName()) {
            'presenze.clienti' => 'clienti',
            'presenze.operatori' => 'operatori',
            default => request()->query('vista', 'gda'),
        };

        if ($this->vista !== 'gda') {
            $this->da = $this->da ?: now()->startOfMonth()->toDateString();
            $this->a = $this->a ?: now()->toDateString();
        }
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    /**
     * @param  array<string, mixed>|null  $valori
     */
    public function applicaFiltri(?array $valori = null): void
    {
        $campiAmmessi = match ($this->vista) {
            'clienti' => ['da', 'a', 'filtroCliente', 'filtroSede'],
            'operatori' => ['da', 'a', 'filtroOperatore'],
            default => ['da', 'a', 'filtroCliente', 'filtroSede', 'filtroOperatore'],
        };
        $filtri = array_intersect_key($valori ?? $this->only($campiAmmessi), array_flip($campiAmmessi));
        $regole = [
            'da' => 'nullable|date',
            'a' => 'nullable|date',
            'filtroCliente' => 'nullable|integer',
            'filtroSede' => 'nullable|integer',
            'filtroOperatore' => 'nullable|integer',
        ];
        $filtri = array_map(fn ($valore) => $valore === '' ? null : $valore, $filtri);
        $filtriValidati = validator($filtri, array_intersect_key($regole, $filtri))->validate();

        foreach ($filtriValidati as $campo => $valore) {
            if (str_starts_with($campo, 'filtro')) {
                $this->{$campo} = $valore === null ? null : (int) $valore;

                continue;
            }

            $this->{$campo} = $valore ?? '';
        }

        $this->resetPage();

        $query = array_filter([
            'da' => $this->da,
            'a' => $this->a,
            'cliente' => $this->filtroCliente,
            'sede' => $this->filtroSede,
            'operatore' => $this->filtroOperatore,
        ], fn ($valore): bool => $valore !== null && $valore !== '');

        $rotta = match ($this->vista) {
            'clienti' => 'presenze.clienti',
            'operatori' => 'presenze.operatori',
            default => 'presenze',
        };

        $this->redirect(route($rotta, $query));
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
        abort_unless(in_array($this->vista, ['gda', 'clienti', 'operatori'], true), 404);

        $clienti = $this->clientiVisibili()->select('tc_clienti.id');
        $filtra = fn ($q) => $this->applicaFiltriDataOra($q)
            ->whereIn('id_cliente', $clienti)
            ->when($this->filtroSede, fn ($q) => $q->where('id_sede', $this->filtroSede))
            ->when($this->filtroCliente, fn ($q) => $q->where('id_cliente', $this->filtroCliente))
            ->when($this->filtroOperatore, fn ($q) => $q->where('id_operatore', $this->filtroOperatore));

        $perPeriodo = fn ($query) => $query
            ->when($this->da, fn ($q) => $q->where('data_inizio', '>=', Carbon::parse($this->da)->toDateString()))
            ->when($this->a, fn ($q) => $q->where('data_inizio', '<=', Carbon::parse($this->a)->toDateString()));

        $gruppi = $perPeriodo(Turno::query())
            ->when($this->filtroOperatore, fn ($q) => $q->where('id_operatore', $this->filtroOperatore))
            ->selectRaw('id_operatore, SUM(ore) as previste, SUM(ore_lavorate) as lavorate')
            ->groupBy('id_operatore')
            ->havingRaw('SUM(ore) > 0')
            ->get();

        $operatoriGruppi = Admin::where('user_type', 4)
            ->whereIn('id', $gruppi->pluck('id_operatore')->filter())
            ->get(['id', 'codice', 'nome', 'cognome'])
            ->keyBy('id');
        $perOperatore = $gruppi->mapWithKeys(fn ($g) => [$g->id_operatore => [
            'codice' => $operatoriGruppi->get($g->id_operatore)?->codice ?? '—',
            'nome' => $operatoriGruppi->get($g->id_operatore)
                ? trim($operatoriGruppi->get($g->id_operatore)->cognome.' '.$operatoriGruppi->get($g->id_operatore)->nome)
                : '—',
            'previste' => (float) $g->previste,
            'lavorate' => (float) $g->lavorate,
        ]])->sortBy('nome');

        $perCliente = $perPeriodo(Turno::query())
            ->whereIn('id_cliente', $clienti)
            ->when($this->filtroSede, fn ($q) => $q->where('id_sede', $this->filtroSede))
            ->when($this->filtroCliente, fn ($q) => $q->where('id_cliente', $this->filtroCliente))
            ->selectRaw('id_cliente, SUM(ore) as previste, SUM(ore_lavorate) as lavorate')
            ->groupBy('id_cliente')
            ->havingRaw('SUM(ore) > 0')
            ->get();
        $nomiClienti = Cliente::whereIn('id', $perCliente->pluck('id_cliente'))->pluck('ragione_sociale', 'id');
        $perCliente = $perCliente->map(fn ($g) => [
            'nome' => $nomiClienti->get($g->id_cliente, '—'), 'previste' => (float) $g->previste, 'lavorate' => (float) $g->lavorate,
        ])->sortBy('nome');

        $dati = [
            'perCliente' => $perCliente,
            'perOperatore' => $perOperatore,
            'sedi' => $this->sediVisibili()->orderBy('descrizione')->get(['id', 'id_cliente', 'descrizione']),
            'clientiElenco' => $this->clientiVisibili()->orderBy('ragione_sociale')->get(['tc_clienti.id', 'ragione_sociale']),
            'operatori' => Admin::where('user_type', 4)->orderBy('cognome')->get(),
        ];

        if ($this->vista === 'gda') {
            $dati['totali'] = $this->totali();
            $dati['turniGda'] = $filtra(Turno::query())
                ->where('stato', 'F')
                ->with(['cliente', 'sede', 'operatore', 'presenze.postazione'])
                ->orderByDesc('data_inizio')->orderByDesc('ora_inizio')
                ->paginate(25, pageName: 'gda');
            $dati['turnoPassaggi'] = $this->passaggiTurnoId ? Turno::with(['sede', 'presenze.postazione'])->find($this->passaggiTurnoId) : null;
        }

        return view('livewire.presenze', $dati);
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
            ->selectRaw('SUM(ore) as previste, SUM(ore_lavorate) as lavorate');
        $righe = $this->applicaFiltriDataOra($righe)->first();

        $previste = (float) $righe->previste;
        $lavorate = (float) $righe->lavorate;

        return ['etichetta' => $etichetta, 'previste' => $previste, 'lavorate' => $lavorate, 'saldo' => $lavorate - $previste];
    }

    private function applicaFiltriDataOra(Builder $query): Builder
    {
        return $query
            ->when($this->da, fn (Builder $builder) => $builder->whereDate('data_inizio', '>=', Carbon::parse($this->da)->toDateString()))
            ->when($this->a, fn (Builder $builder) => $builder->whereDate('data_inizio', '<=', Carbon::parse($this->a)->toDateString()));
    }
}

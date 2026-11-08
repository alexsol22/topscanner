<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Gda;
use App\Models\Legacy\Turno;
use App\Services\Calcoli;
use App\Services\Comunicazioni;
use App\Services\PianificazioneTurni;
use App\Services\Timbratura;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Agenda Pos')]
class Turni extends Component
{
    use ConAccesso, WithPagination;

    public string $da = '';

    public string $a = '';

    #[Url(as: 'sede')]
    public ?int $filtroSede = null;

    #[Url(as: 'operatore')]
    public ?int $filtroOperatore = null;

    public ?int $filtroCliente = null;

    public string $filtroStato = '';

    public string $vista = 'calendario';

    public string $periodoCalendario = 'mese';

    public string $mese = '';

    public string $selezioneMese = '';

    public string $selezioneData = '';

    public bool $modal = false;

    public ?int $turnoSelezionatoId = null;

    public ?int $turnoModificaId = null;

    public array $m = [];

    public array $f = [];

    public array $giorni = [];

    public ?string $esito = null;

    public array $ac = [];

    public bool $modalInvio = false;

    public array $invio = [];

    public ?string $esitoInvio = null;

    public function mount(): void
    {
        $this->mese = today()->toDateString();
        $this->allineaSelezioniCalendario();
    }

    public function updating($name): void
    {
        if (in_array($name, ['da', 'a', 'filtroSede', 'filtroOperatore', 'filtroCliente', 'filtroStato', 'vista'])) {
            $this->resetPage();
        }
    }

    public function selezionaSuggerimento(string $tipo, int $id): void
    {
        abort_unless(in_array($tipo, ['sede', 'operatore'], true), 422);

        if ($tipo === 'sede') {
            $this->sediVisibili()->findOrFail($id);
            abort_unless($this->query()->where('id_sede', $id)->exists(), 404);
            $this->filtroSede = $id;

            return;
        }

        abort_unless($this->query()->where('id_operatore', $id)->exists(), 404);
        $this->filtroOperatore = $id;
    }

    public function rimuoviFiltro(string $tipo): void
    {
        abort_unless(in_array($tipo, ['sede', 'operatore'], true), 422);

        if ($tipo === 'sede') {
            $this->filtroSede = null;

            return;
        }

        $this->filtroOperatore = null;
    }

    public function oggi(): void
    {
        $this->mese = today()->toDateString();
        $this->allineaSelezioniCalendario();
    }

    public function cambiaPeriodoCalendario(string $periodo): void
    {
        abort_unless(in_array($periodo, ['mese', 'settimana', 'giorno'], true), 422);

        $this->periodoCalendario = $periodo;
        $this->allineaSelezioniCalendario();
    }

    public function updated(string $name): void
    {
        if ($name === 'selezioneMese') {
            $this->validateOnly('selezioneMese', ['selezioneMese' => 'required|date_format:Y-m']);
            $this->mese = Carbon::createFromFormat('!Y-m', $this->selezioneMese)->toDateString();
            $this->allineaSelezioniCalendario();

            return;
        }

        if ($name === 'selezioneData') {
            $this->validateOnly('selezioneData', ['selezioneData' => 'required|date_format:Y-m-d']);
            $this->mese = Carbon::parse($this->selezioneData)->toDateString();
            $this->allineaSelezioniCalendario();
        }
    }

    public function cambiaPeriodo(int $delta): void
    {
        $giorno = Carbon::parse($this->mese);

        $this->mese = match ($this->periodoCalendario) {
            'settimana' => $giorno->addWeeks($delta)->toDateString(),
            'giorno' => $giorno->addDays($delta)->toDateString(),
            default => $giorno->startOfMonth()->addMonths($delta)->toDateString(),
        };
        $this->allineaSelezioniCalendario();
    }

    public function vaiAlGiorno(string $data): void
    {
        $this->mese = Carbon::parse($data)->toDateString();
        $this->periodoCalendario = 'giorno';
        $this->allineaSelezioniCalendario();
    }

    public function mostraTurno(int $id): void
    {
        $this->turnoSelezionatoId = $this->query()->findOrFail($id)->id;
    }

    public function chiudiDettaglioTurno(): void
    {
        $this->turnoSelezionatoId = null;
    }

    public function modifica(int $id): void
    {
        $this->soloStaff();
        $t = $this->query()->findOrFail($id);
        $this->m = [
            'data_inizio' => $t->data_inizio->toDateString(), 'ora_inizio' => $t->ora_inizio,
            'data_fine' => $t->data_fine->toDateString(), 'ora_fine' => $t->ora_fine,
        ];
        $this->turnoModificaId = $t->id;
        $this->turnoSelezionatoId = null;
        $this->resetValidation();
    }

    public function chiudiModifica(): void
    {
        $this->turnoModificaId = null;
    }

    public function aggiorna(): void
    {
        $this->soloStaff();
        $this->validate([
            'm.data_inizio' => 'required|date',
            'm.data_fine' => 'required|date',
            'm.ora_inizio' => 'required|date_format:H:i',
            'm.ora_fine' => 'required|date_format:H:i',
        ]);

        $turno = $this->query()->findOrFail($this->turnoModificaId);
        $inizio = Carbon::parse($this->m['data_inizio'].' '.$this->m['ora_inizio']);
        $fine = Carbon::parse($this->m['data_fine'].' '.$this->m['ora_fine']);
        if ($fine->lte($inizio)) {
            $this->addError('m.ora_fine', 'La fine deve essere successiva all\'inizio.');

            return;
        }

        $turno->update([
            'data_inizio' => $inizio->toDateString(), 'ora_inizio' => $inizio->format('H:i'),
            'data_fine' => $fine->toDateString(), 'ora_fine' => $fine->format('H:i'),
            'ore' => Calcoli::ore($inizio, $fine),
        ]);
        $this->turnoModificaId = null;
    }

    public function nuovoGiorno(string $data): void
    {
        $this->nuovo();
        $this->f['data_inizio'] = $this->f['data_fine'] = Carbon::parse($data)->toDateString();
    }

    public function nuovo(): void
    {
        $this->soloStaff();
        $this->f = [
            'cliente_id' => '', 'sede_id' => '', 'operatore_id' => '',
            'data_inizio' => today()->toDateString(), 'ora_inizio' => '08:00',
            'data_fine' => today()->toDateString(), 'ora_fine' => '16:00',
            'ripeti_fino' => '', 'note' => '',
        ];
        $this->giorni = [];
        $this->esito = null;
        $this->resetValidation();
        $this->modal = true;
    }

    public function updatedFClienteId(): void
    {
        $this->f['sede_id'] = '';
        $this->f['operatore_id'] = '';
    }

    private function intervallo(): ?array
    {
        try {
            $i = Carbon::parse(($this->f['data_inizio'] ?? '').' '.($this->f['ora_inizio'] ?? ''));
            $f = Carbon::parse(($this->f['data_fine'] ?? '').' '.($this->f['ora_fine'] ?? ''));
        } catch (\Throwable) {
            return null;
        }

        return [$i, $f];
    }

    public function salva(PianificazioneTurni $piano): void
    {
        $this->soloStaff();
        $this->validate([
            'f.cliente_id' => 'required|exists:tc_clienti,id',
            'f.sede_id' => 'required|exists:tc_sedi,id',
            'f.operatore_id' => 'required|exists:tc_admin,id',
            'f.data_inizio' => 'required|date',
            'f.data_fine' => 'required|date',
            'f.ora_inizio' => 'required|date_format:H:i',
            'f.ora_fine' => 'required|date_format:H:i',
            'f.ripeti_fino' => 'nullable|date|after:f.data_inizio',
        ]);

        $sede = $this->sediVisibili()->where('id_cliente', $this->f['cliente_id'])->findOrFail($this->f['sede_id']);
        [$inizio, $fine] = $this->intervallo();
        if ($fine->lte($inizio)) {
            $this->addError('f.ora_fine', 'La fine deve essere successiva all\'inizio.');

            return;
        }

        $ripetiFino = $this->f['ripeti_fino'] ? Carbon::parse($this->f['ripeti_fino']) : null;
        $creati = 0;
        $saltati = [];

        foreach ($piano->occorrenze($inizio, $fine, $ripetiFino, array_map('intval', $this->giorni)) as $o) {
            $libero = $piano->operatoriDisponibili($o['inizio'], $o['fine'], $sede->id_cliente)->contains('id', (int) $this->f['operatore_id']);
            if (! $libero) {
                $saltati[] = $o['inizio']->format('d/m');

                continue;
            }
            Turno::create([
                'id_operatore' => $this->f['operatore_id'], 'id_cliente' => $sede->id_cliente, 'id_sede' => $sede->id,
                'data_inizio' => $o['inizio']->toDateString(), 'ora_inizio' => $o['inizio']->format('H:i'),
                'data_fine' => $o['fine']->toDateString(), 'ora_fine' => $o['fine']->format('H:i'),
                'ore' => Calcoli::ore($o['inizio'], $o['fine']), 'stato' => 'P',
                'note_operatore' => $this->f['note'] ?: null,
            ]);
            $creati++;
        }

        if ($saltati) {
            $this->esito = "Creati $creati turni. Operatore non disponibile il: ".implode(', ', $saltati).'.';
            if (! $creati) {
                return;
            }
        }
        $this->modal = $saltati ? true : false;
    }

    public function elimina(int $id): void
    {
        $this->soloStaff();
        $this->query()->findOrFail($id)->delete();
    }

    /** Apre il modulo di inizio/chiusura manuale (apriChiudi di Yii): nota, data e ora sono obbligatorie. */
    public function apriChiudi(int $id): void
    {
        $this->soloStaff();
        $t = $this->query()->findOrFail($id);

        if (! in_array($t->stato, ['P', 'I'], true)) {
            return;
        }

        $previsto = $t->stato === 'P' ? $t->inizio : $t->fine;
        $this->ac = ['id' => $t->id, 'stato' => $t->stato === 'P' ? 'I' : 'F', 'data' => $previsto->toDateString(), 'ora' => $previsto->format('H:i'), 'note' => ''];
        $this->resetValidation();
    }

    public function annullaApriChiudi(): void
    {
        $this->ac = [];
    }

    /** Apertura/chiusura manuale del turno da parte dell'amministrazione (apriChiudiTurno di Yii). */
    public function confermaApriChiudi(Timbratura $timbratura): void
    {
        $this->soloStaff();
        $this->validate([
            'ac.note' => 'required|string|max:255',
            'ac.data' => 'required|date',
            'ac.ora' => 'required|date_format:H:i',
        ], [
            'ac.note.required' => 'Inserire la nota di chiusura / inizio turno.',
            'ac.data.required' => 'Inserire il giorno.',
            'ac.ora.required' => 'Inserire l\'orario.',
        ]);

        $t = $this->query()->findOrFail($this->ac['id']);
        $stato = $t->stato === 'P' ? 'I' : 'F';

        if (! in_array($t->stato, ['P', 'I'], true)) {
            $this->ac = [];

            return;
        }

        $timbratura->apriChiudi($t, auth()->user(), $stato, \Illuminate\Support\Carbon::parse($this->ac['data'].' '.$this->ac['ora']), $this->ac['note']);
        $this->ac = [];
    }

    public function nuovoInvio(): void
    {
        $this->soloStaff();
        $this->invio = [
            'cliente' => $this->filtroCliente, 'sede' => $this->filtroSede, 'operatore' => $this->filtroOperatore,
            'inizio' => $this->da, 'fine' => $this->a,
            'testo' => 'Ti ricordiamo l\'inizio del turno il [DATA TURNO] Alle [ORA TURNO] presso la sede [SEDE] al seguente indirizzo [INDIRIZZO]',
        ];
        $this->esitoInvio = null;
        $this->modalInvio = true;
    }

    /** Accoda i promemoria dei turni agli operatori (sendTurni di Yii). */
    public function inviaTurni(Comunicazioni $comunicazioni): void
    {
        $this->soloStaff();
        $this->validate([
            'invio.cliente' => 'nullable|integer',
            'invio.sede' => 'nullable|integer',
            'invio.operatore' => 'nullable|integer',
            'invio.inizio' => 'nullable|date',
            'invio.fine' => 'nullable|date',
            'invio.testo' => 'required|string|max:2000',
        ]);

        $esito = $comunicazioni->accodaPromemoriaTurni(auth()->user(), [
            'cliente' => $this->invio['cliente'] ?: null,
            'sede' => $this->invio['sede'] ?: null,
            'operatore' => $this->invio['operatore'] ?: null,
            'inizio' => $this->invio['inizio'] ?: null,
            'fine' => $this->invio['fine'] ?: null,
        ], $this->invio['testo']);

        $this->esitoInvio = $esito
            ? "Email turno inviate correttamente. Totale operatori coinvolti {$esito['operatori']}"
            : 'Non sono stati trovati turni a cui inviare comunicazioni per sede, operatore, data indicate.';
    }

    public function chiudiInvio(): void
    {
        $this->modalInvio = false;
    }

    private function allineaSelezioniCalendario(): void
    {
        $riferimento = Carbon::parse($this->mese);
        $this->selezioneMese = $riferimento->format('Y-m');
        $this->selezioneData = $riferimento->toDateString();
    }

    private function query(): Builder
    {
        $u = auth()->user();

        return Turno::query()
            ->when($u->isOperatore(), fn ($q) => $q->where('id_operatore', $u->id))
            ->when(! $u->isOperatore(), fn ($q) => $q->whereIn('id_cliente', $this->clientiVisibili()->select('tc_clienti.id')));
    }

    private function applicaRicerca(Builder $query): Builder
    {
        return $query
            ->when($this->filtroSede, fn (Builder $query) => $query->where('id_sede', $this->filtroSede))
            ->when($this->filtroOperatore, fn (Builder $query) => $query->where('id_operatore', $this->filtroOperatore))
            ->when($this->filtroCliente, fn (Builder $query) => $query->where('id_cliente', $this->filtroCliente))
            ->when($this->filtroStato !== '', fn (Builder $query) => $this->filtraPerStato($query, $this->filtroStato))
            ->when($this->da, fn (Builder $query) => $query->where('data_inizio', '>=', $this->da))
            ->when($this->a, fn (Builder $query) => $query->where('data_inizio', '<=', $this->a));
    }

    /** Stessi stati di Turno::statoEffettivo (legenda dei colori di Yii). */
    private function filtraPerStato(Builder $query, string $stato): Builder
    {
        $adesso = now()->format('Y-m-d H:i');
        $inizio = "CONCAT(data_inizio, ' ', ora_inizio)";
        $fine = "CONCAT(data_fine, ' ', ora_fine)";

        return match ($stato) {
            'completato' => $query->where('stato', 'F'),
            'in_corso' => $query->where('stato', 'I')->whereRaw("$fine >= ?", [$adesso]),
            'non_completato' => $query->where('stato', 'I')->whereRaw("$fine < ?", [$adesso]),
            'non_eseguito' => $query->whereNotIn('stato', ['F', 'I'])->whereRaw("$inizio < ? AND $fine < ?", [$adesso, $adesso]),
            'non_timbrato' => $query->whereNotIn('stato', ['F', 'I'])->whereRaw("$inizio < ? AND $fine >= ?", [$adesso, $adesso]),
            'programmato' => $query->whereNotIn('stato', ['F', 'I'])->whereRaw("$inizio >= ?", [$adesso]),
            default => $query,
        };
    }

    private function totaleOreAgenda(): ?float
    {
        if (! $this->filtroCliente && ! $this->filtroSede && ! $this->filtroOperatore && $this->filtroStato === '' && ! $this->da && ! $this->a) {
            return null;
        }

        return (float) $this->applicaRicerca($this->query())->sum('ore');
    }

    private function calendario(): array
    {
        $riferimento = Carbon::parse($this->mese);
        $primo = $this->periodoCalendario === 'mese' ? $riferimento->copy()->startOfMonth() : $riferimento->copy();
        [$da, $a] = match ($this->periodoCalendario) {
            'settimana' => [$primo->copy()->startOfWeek(), $primo->copy()->endOfWeek()],
            'giorno' => [$primo->copy()->startOfDay(), $primo->copy()->endOfDay()],
            default => [$primo->copy()->startOfMonth()->startOfWeek(), $primo->copy()->endOfMonth()->endOfWeek()],
        };
        $perGiorno = $this->applicaRicerca($this->query()->with([
            'operatore',
            'sede',
            'presenze' => fn ($query) => $query->whereIn('tipo', [Gda::INIZIO, Gda::FINE])->orderBy('data')->orderBy('orario'),
        ]))
            ->whereBetween('data_inizio', [$da->toDateString(), $a->toDateString()])
            ->orderBy('data_inizio')->orderBy('ora_inizio')->get()
            ->groupBy(fn ($t) => $t->inizio->toDateString());

        return ['primo' => $primo, 'da' => $da, 'a' => $a, 'turni' => $perGiorno];
    }

    public function render(PianificazioneTurni $piano)
    {
        $turni = null;
        if ($this->vista === 'lista') {
            $turni = $this->applicaRicerca($this->query()->with([
                'operatore',
                'sede.cliente',
                'presenze' => fn ($query) => $query->whereIn('tipo', [Gda::INIZIO, Gda::FINE])->orderBy('data')->orderBy('orario'),
            ]))
                ->orderBy('data_inizio')->orderBy('ora_inizio')->paginate(15);
        }
        $calendario = $this->vista === 'lista' ? null : $this->calendario();

        $turnoDettaglio = $this->turnoSelezionatoId
            ? $this->query()->with([
                'operatore',
                'sede.cliente',
                'presenze' => fn ($query) => $query->whereIn('tipo', [Gda::INIZIO, Gda::FINE])->orderBy('data')->orderBy('orario'),
            ])->findOrFail($this->turnoSelezionatoId)
            : null;

        $disponibili = collect();
        $sediForm = collect();
        if ($this->modal) {
            $sediForm = $this->sediVisibili()->where('id_cliente', $this->f['cliente_id'] ?: 0)->orderBy('descrizione')->get(['id', 'descrizione']);
            $intervallo = $this->intervallo();
            if ($intervallo !== null && $intervallo[1]->gt($intervallo[0])) {
                $disponibili = $piano->operatoriDisponibili($intervallo[0], $intervallo[1], $this->f['cliente_id'] ?: null);
            }
        }

        $suggerimentiSedi = $this->sediVisibili()
            ->whereIn('id', $this->query()->select('id_sede')->distinct())
            ->orderBy('descrizione')
            ->get()
            ->map(fn ($sede) => [
                'id' => $sede->id,
                'label' => $sede->descrizione,
                'detail' => $sede->citta,
                'search' => $sede->descrizione.' '.$sede->citta,
            ])
            ->values();

        $suggerimentiOperatori = Admin::query()
            ->whereIn('id', $this->query()->select('id_operatore')->distinct())
            ->orderBy('cognome')
            ->get()
            ->map(fn ($operatore) => [
                'id' => $operatore->id,
                'label' => $operatore->nome_completo,
                'detail' => $operatore->email,
                'search' => $operatore->nome_completo.' '.$operatore->email,
            ])
            ->values();

        $sedeSelezionata = $this->filtroSede ? $this->sediVisibili()->find($this->filtroSede) : null;
        $operatoreSelezionato = $this->filtroOperatore
            ? $this->query()->where('id_operatore', $this->filtroOperatore)->with('operatore')->first()?->operatore
            : null;

        $turnoModifica = $this->turnoModificaId
            ? $this->query()->with(['operatore', 'sede.cliente'])->find($this->turnoModificaId)
            : null;

        $clienti = $this->clientiVisibili()->orderBy('ragione_sociale')->get(['id', 'ragione_sociale']);

        return view('livewire.turni', [
            'totaleOreAgenda' => $this->totaleOreAgenda(),
            'sediInvio' => $this->modalInvio ? $this->sediVisibili()->orderBy('descrizione')->get(['id', 'descrizione']) : collect(),
            'operatoriInvio' => $this->modalInvio ? Admin::where('user_type', 4)->where('attivo', 'Y')->orderBy('cognome')->get() : collect(),
            'turnoModifica' => $turnoModifica,
            'turni' => $turni,
            'calendario' => $calendario,
            'turnoDettaglio' => $turnoDettaglio,
            'suggerimentiSedi' => $suggerimentiSedi,
            'suggerimentiOperatori' => $suggerimentiOperatori,
            'sedeSelezionata' => $sedeSelezionata,
            'operatoreSelezionato' => $operatoreSelezionato,
            'clienti' => $clienti,
            'clienteSelezionato' => $clienti->firstWhere('id', $this->filtroCliente),
            'sediForm' => $sediForm,
            'disponibili' => $disponibili,
            'giorniSettimana' => [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'],
        ]);
    }
}

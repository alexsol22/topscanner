<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Codice;
use App\Models\Legacy\Documento;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Tracking;
use App\Models\Legacy\Turno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Sedi')]
class Sedi extends Component
{
    use ConAccesso, WithPagination;

    public string $filtroDenominazione = '';

    public string $filtroEmail = '';

    public string $filtroTelefono = '';

    #[Url(as: 'cliente')]
    public ?int $filtroCliente = null;

    public bool $modalPostazioni = false;

    public ?int $sedePostazioni = null;

    public ?int $eliminaId = null;

    public string $conferma = '';

    public function mount(): void
    {
        Tracking::registra('Visualizzazione elenco Sedi ');
    }

    public function updatingFiltroDenominazione(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEmail(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTelefono(): void
    {
        $this->resetPage();
    }

    public function richiediEliminazione(int $id): void
    {
        $this->soloStaff();
        $this->reset('conferma');
        $this->resetErrorBag();
        $this->eliminaId = $this->sediVisibili()->findOrFail($id)->id;
    }

    public function annullaEliminazione(): void
    {
        $this->reset('eliminaId', 'conferma');
        $this->resetErrorBag();
    }

    private function confermaValida(string $atteso): bool
    {
        $normalizza = fn (string $v): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v)));

        return $normalizza($this->conferma) === $normalizza($atteso);
    }

    /** Stessa cascata del vecchio removeRelated, con le chiavi corrette (id_sede) e pulizia di file e codici. */
    public function elimina(): void
    {
        $this->soloStaff();
        abort_unless($this->eliminaId, 404);
        $sede = $this->sediVisibili()->findOrFail($this->eliminaId);

        if (! $this->confermaValida('conferma')) {
            $this->addError('conferma', 'Il testo non coincide: eliminazione non eseguita.');

            return;
        }

        // Niente transazione: tabelle legacy MyISAM e GTID.
        $idCodici = $sede->postazioni()->pluck('id_codice')->push($sede->id_codice)->filter()->all();

        Documento::where('tipo', 'S')->where('id_tipo', $sede->id)->delete();
        Turno::where('id_sede', $sede->id)->delete();
        DB::connection('mysql')->table('tc_turni_step')->where('id_sede', $sede->id)->delete();
        Postazione::where('id_sede', $sede->id)->delete();
        $this->liberaCodici($idCodici);
        Storage::disk(config('filesystems.files_disk'))->deleteDirectory($sede->cartella());
        $sede->delete();

        Tracking::registra('Rimozione sede '.$sede->descrizione);
        $this->annullaEliminazione();
        session()->flash('esito', "Sede {$sede->descrizione} eliminata.");
    }

    /** @param array<int, int> $ids */
    private function liberaCodici(array $ids): void
    {
        if ($ids !== []) {
            Codice::whereIn('id', $ids)->update(['stato' => Codice::CREATO, 'tipo' => null, 'id_sede' => null, 'id_postazione' => null]);
        }
    }

    /** CSV dell'elenco (filtri cliente e ricerca inclusi), apribile in Excel. */
    public function esporta()
    {
        $this->soloStaff();
        $cliente = $this->clienteCorrente();
        abort_unless($cliente, 404);
        $sedi = $this->querySedi($cliente->id)->with(['cliente', 'comune', 'provincia'])->withCount(['postazioni', 'turni'])->orderBy('descrizione')->get();
        Tracking::registra('Esportazione elenco Sedi');

        return response()->streamDownload(function () use ($sedi) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Cliente', 'Sede', 'Indirizzo', 'CAP', 'Città', 'Provincia', 'Telefono', 'Email', 'Postazioni', 'Turni', 'Inizio contratto', 'Fine contratto'], ';');
            foreach ($sedi as $s) {
                fputcsv($f, [
                    $s->cliente?->ragione_sociale, $s->descrizione, $s->indirizzo, $s->cap, $s->citta, $s->provincia_sigla,
                    $s->telefono, $s->email, $s->postazioni_count, $s->turni_count,
                    $s->inizio_contratto?->format('d-m-Y'), $s->fine_contratto?->format('d-m-Y'),
                ], ';');
            }
            fclose($f);
        }, 'sedi-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function clienteCorrente(): ?Cliente
    {
        $clienteId = auth()->user()->isCliente()
            ? auth()->user()->id_cliente
            : $this->filtroCliente;

        return $clienteId ? $this->clientiVisibili()->findOrFail($clienteId) : null;
    }

    private function querySedi(?int $clienteId): Builder
    {
        return $this->sediVisibili()
            ->when($clienteId === null, fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->where('id_cliente', $clienteId))
            ->when($this->filtroDenominazione, fn ($q) => $q->where('descrizione', 'like', "%{$this->filtroDenominazione}%"))
            ->when($this->filtroEmail, fn ($q) => $q->where('email', 'like', "%{$this->filtroEmail}%"))
            ->when($this->filtroTelefono, fn ($q) => $q->where('telefono', 'like', "%{$this->filtroTelefono}%"));
    }

    public function postazioni(int $id): void
    {
        $this->sedePostazioni = $this->sediVisibili()->findOrFail($id)->id;
        $this->resetValidation();
        $this->modalPostazioni = true;
    }

    public function eliminaPostazione(int $id): void
    {
        $this->soloStaff();
        $postazione = Postazione::whereIn('id_sede', $this->sediVisibili()->select('tc_sedi.id'))->findOrFail($id);

        Storage::disk(config('filesystems.files_disk'))->deleteDirectory($postazione->cartella());
        $this->liberaCodici(array_filter([$postazione->id_codice]));
        $postazione->delete();
    }

    public function render()
    {
        $clienteCorrente = $this->clienteCorrente();
        $sedi = $this->querySedi($clienteCorrente?->id)->with(['codice', 'comune'])->withCount(['turni', 'turni as turni_conclusi_count' => fn ($q) => $q->where('stato', 'F')])
            ->orderBy('descrizione')->paginate(9);

        $daEliminare = $this->eliminaId ? $this->sediVisibili()->find($this->eliminaId) : null;

        return view('livewire.sedi', [
            'sedi' => $sedi,
            'clienteCorrente' => $clienteCorrente,
            'daEliminare' => $daEliminare,
            'riepilogo' => $daEliminare ? [
                'turni' => Turno::where('id_sede', $daEliminare->id)->count(),
                'presenze' => $daEliminare->presenze()->count(),
                'postazioni' => $daEliminare->postazioni()->count(),
            ] : null,
            'sedeCorrente' => $this->sedePostazioni
                ? $this->sediVisibili()->with(['codice', 'comune', 'provincia', 'postazioni.codice'])->find($this->sedePostazioni)
                : null,
        ]);
    }
}

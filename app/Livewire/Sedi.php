<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Codice;
use App\Models\Legacy\Documento;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Tracking;
use App\Models\Legacy\Turno;
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

    public string $cerca = '';

    #[Url(as: 'cliente')]
    public ?int $filtroCliente = null;

    public bool $modalPostazioni = false;

    public ?int $sedeQr = null;

    public ?int $sedePostazioni = null;

    public ?int $eliminaId = null;

    public string $conferma = '';

    public array $p = ['titolo' => '', 'minuti_passaggio' => ''];

    public function mount(): void
    {
        Tracking::registra('Visualizzazione elenco Sedi ');
    }

    public function updatingCerca(): void
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
        $sedi = $this->querySedi()->with(['cliente', 'comune', 'provincia'])->withCount(['postazioni', 'turni'])->orderBy('descrizione')->get();
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

    private function querySedi()
    {
        return $this->sediVisibili()
            ->when($this->filtroCliente, fn ($q) => $q->where('id_cliente', $this->filtroCliente))
            ->when($this->cerca, fn ($q) => $q->where(fn ($w) => $w->where('descrizione', 'like', "%{$this->cerca}%")
                ->orWhereHas('comune', fn ($c) => $c->where('comune', 'like', "%{$this->cerca}%"))));
    }

    public function qr(int $id): void
    {
        $this->soloStaff();
        $this->sedeQr = $this->sediVisibili()->findOrFail($id)->id;
    }

    public function chiudiQr(): void
    {
        $this->sedeQr = null;
    }

    public function postazioni(int $id): void
    {
        $this->sedePostazioni = $this->sediVisibili()->findOrFail($id)->id;
        $this->p = ['titolo' => '', 'minuti_passaggio' => ''];
        $this->resetValidation();
        $this->modalPostazioni = true;
    }

    public function aggiungiPostazione(): void
    {
        $this->soloStaff();
        $this->validate([
            'p.titolo' => 'required|string|max:255',
            'p.minuti_passaggio' => 'nullable|integer|min:1|max:1440',
        ]);
        $sede = $this->sediVisibili()->findOrFail($this->sedePostazioni);

        $post = Postazione::create([
            'id_sede' => $sede->id, 'id_cliente' => $sede->id_cliente,
            'titolo' => $this->p['titolo'], 'minuti' => $this->p['minuti_passaggio'] ?: null,
        ]);
        $post->update(['id_codice' => Codice::nuovo(Codice::POSTAZIONE, $sede->id, $post->id)->id]);
        $this->p = ['titolo' => '', 'minuti_passaggio' => ''];
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
        $sedi = $this->querySedi()->with(['cliente', 'comune'])->withCount(['postazioni', 'turni', 'turni as turni_conclusi_count' => fn ($q) => $q->where('stato', 'F')])
            ->orderBy('descrizione')->paginate(9);

        $daEliminare = $this->eliminaId ? $this->sediVisibili()->find($this->eliminaId) : null;

        return view('livewire.sedi', [
            'sedi' => $sedi,
            'daEliminare' => $daEliminare,
            'riepilogo' => $daEliminare ? [
                'turni' => Turno::where('id_sede', $daEliminare->id)->count(),
                'presenze' => $daEliminare->presenze()->count(),
                'postazioni' => $daEliminare->postazioni()->count(),
            ] : null,
            'clienti' => $this->clientiVisibili()->orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
            'sedeCorrente' => $this->sedePostazioni ? $this->sediVisibili()->with('postazioni.codice', 'codice')->find($this->sedePostazioni) : null,
        ]);
    }
}

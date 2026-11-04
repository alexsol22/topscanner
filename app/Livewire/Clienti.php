<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Mail\NotificaHtml;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Comune;
use App\Models\Legacy\Documento;
use App\Models\Legacy\OperatoreEscludi;
use App\Models\Legacy\OperatorePreferito;
use App\Models\Legacy\Provincia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Clienti')]
class Clienti extends Component
{
    use ConAccesso, WithFileUploads, WithPagination;

    private const ICONE = ['png' => 'file-image', 'jpg' => 'file-image', 'jpeg' => 'file-image', 'pdf' => 'file-pdf', 'xls' => 'file-excel', 'xlsx' => 'file-excel', 'zip' => 'file-archive', 'mp4' => 'file-video'];

    public string $cerca = '';

    #[Url(as: 'form')]
    public bool $modal = false;

    #[Url(as: 'id')]
    public ?int $clienteId = null;

    public array $f = [];

    #[Url]
    public string $tab = 'dati';

    /** @var array<int, int|string> */
    public array $preferiti = [];

    /** @var array<int, int|string> */
    public array $escludi = [];

    public string $titoloDocumento = '';

    public $fileDocumento;

    private const CAMPI = ['ragione_sociale', 'codice_fiscale', 'piva', 'indirizzo', 'cap', 'citta_id', 'provincia_id', 'nome', 'cognome', 'telefono', 'cellulare', 'fax', 'email', 'pec', 'accesso', 'colore'];

    protected function rules(): array
    {
        $telefono = ['nullable', 'regex:/^[0-9 \/.\-+]+$/'];

        return [
            'f.ragione_sociale' => 'required|string|max:255',
            'f.nome' => 'nullable|string|max:255',
            'f.cognome' => 'nullable|string|max:255',
            'f.codice_fiscale' => 'nullable|string|max:16',
            'f.piva' => 'required|digits:11',
            'f.indirizzo' => 'nullable|string|max:200',
            'f.cap' => 'nullable|string|max:5',
            'f.colore' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'f.citta_id' => 'nullable|integer',
            'f.provincia_id' => 'nullable|integer',
            'f.email' => 'required|email|max:255',
            'f.pec' => 'nullable|email|max:255',
            'f.cellulare' => [...$telefono, 'max:20'],
            'f.telefono' => [...$telefono, 'max:16'],
            'f.fax' => [...$telefono, 'max:15'],
        ];
    }

    public function updatingCerca(): void
    {
        $this->resetPage();
    }

    /** Dopo un refresh il form riapre lo stesso record (stato preso dall'URL). */
    public function mount(): void
    {
        if (! $this->modal) {
            return;
        }

        $tab = in_array($this->tab, ['dati', 'formazioni', 'attestati', 'materiali', 'documenti', 'operatori'], true) ? $this->tab : 'dati';
        $id = $this->clienteId;

        try {
            $id ? $this->modifica($id) : $this->nuovo();
            $this->tab = $tab;
        } catch (ModelNotFoundException) {
            $this->modal = false;
            $this->clienteId = null;
        }
    }

    public function nuovo(): void
    {
        $this->reset('clienteId');
        $this->f = array_fill_keys(self::CAMPI, '');
        $this->f['accesso'] = false;
        $this->f['colore'] = '#1d3e67';
        $this->preferiti = $this->escludi = [];
        $this->reset('tab', 'titoloDocumento', 'fileDocumento');
        $this->resetValidation();
        $this->modal = true;
    }

    public function modifica(int $id): void
    {
        $c = $this->clientiVisibili()->findOrFail($id);
        $this->clienteId = $c->id;
        $this->f = collect(self::CAMPI)->mapWithKeys(fn ($k) => [$k => $c->$k instanceof \DateTimeInterface ? $c->$k->format('Y-m-d') : ($c->$k ?? '')])->all();
        $this->f['colore'] = $this->f['colore'] ?: '#1d3e67';
        foreach (['provincia_id', 'citta_id'] as $campo) {
            $this->f[$campo] = (string) ($this->f[$campo] ?: '');
        }
        $this->preferiti = OperatorePreferito::where('id_cliente', $c->id)->pluck('id_operatore')->map(fn ($i) => (string) $i)->all();
        $this->escludi = OperatoreEscludi::where('id_cliente', $c->id)->pluck('id_operatore')->map(fn ($i) => (string) $i)->all();
        $this->reset('tab', 'titoloDocumento', 'fileDocumento');
        $this->resetValidation();
        $this->modal = true;
    }

    public function updatedFProvinciaId(): void
    {
        $this->f['citta_id'] = '';
    }

    public function aggiungiDocumento(): void
    {
        $this->soloStaff();
        $this->validate([
            'titoloDocumento' => 'required|string|max:255',
            'fileDocumento' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,xls,xlsx,mp4,zip',
        ], [], ['titoloDocumento' => 'titolo', 'fileDocumento' => 'file']);

        $cliente = $this->clientiVisibili()->findOrFail($this->clienteId);
        $estensione = strtolower($this->fileDocumento->getClientOriginalExtension());
        $documento = new Documento([
            'id_tipo' => $cliente->id, 'tipo' => 'C', 'descrizione' => $this->titoloDocumento,
            'icona' => self::ICONE[$estensione] ?? 'file-alt', 'admin' => 'N', 'formazione' => 'N',
        ]);
        $documento->file = 'Doc_'.$cliente->id.'_'.time().'.'.$estensione;

        $this->fileDocumento->storeAs(dirname($documento->percorso()), basename($documento->percorso()), ['disk' => config('filesystems.files_disk')]);
        $documento->save();

        $this->reset('titoloDocumento', 'fileDocumento');
    }

    public function eliminaDocumento(int $id): void
    {
        $this->soloStaff();
        $documento = Documento::where('tipo', 'C')->where('id_tipo', $this->clientiVisibili()->findOrFail($this->clienteId)->id)->findOrFail($id);
        Storage::disk(config('filesystems.files_disk'))->delete($documento->percorso());
        $documento->delete();
    }

    /** Come Yii (setOperatoriPreferiti / setOperatoriEscludi): cancella e reinserisce. */
    private function salvaOperatori(int $clienteId): void
    {
        foreach ([[OperatorePreferito::class, $this->preferiti], [OperatoreEscludi::class, $this->escludi]] as [$modello, $ids]) {
            $modello::where('id_cliente', $clienteId)->delete();

            foreach (array_unique(array_map('intval', $ids)) as $idOperatore) {
                $modello::create(['id_cliente' => $clienteId, 'id_operatore' => $idOperatore]);
            }
        }
    }

    public function salva(): void
    {
        $this->soloStaff();

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->tab = 'dati';
            $this->dispatch('scorri-al-primo-errore');

            throw $e;
        }

        $dati = array_map(fn ($v) => $v === '' ? null : $v, $this->f);
        $dati['accesso'] = ! empty($this->f['accesso']) && $this->f['accesso'] !== 'N' ? 'Y' : 'N';
        $dati['cellulare'] ??= '';

        $nuovoAccount = null;

        DB::connection('mysql')->transaction(function () use ($dati, &$nuovoAccount) {
            if ($this->clienteId) {
                $cliente = $this->clientiVisibili()->findOrFail($this->clienteId);
                $cliente->update($dati);
            } else {
                $cliente = Cliente::create($dati + ['pos' => '', 'lat' => 0, 'lng' => 0, 'isTest' => (int) auth()->user()->user_type === 1 ? 1 : null]);
            }

            $this->salvaOperatori($cliente->id);

            if ($dati['accesso'] === 'Y') {
                $nuovoAccount = $this->creaAccount($cliente);
            }
        });

        $this->reset('modal', 'clienteId', 'tab');
        session()->flash('esito', 'Cliente salvato.'.($nuovoAccount ? $this->inviaCredenziali($nuovoAccount) : ''));
    }

    /**
     * Come Yii (Clienti::addAccount): utente tipo 3 con password casuale, solo se non esiste già.
     *
     * @return array{0: Admin, 1: string}|null account creato e password in chiaro da comunicare
     */
    private function creaAccount(Cliente $cliente): ?array
    {
        if (Admin::where('id_cliente', $cliente->id)->exists()) {
            return null;
        }

        $password = Str::password(8, symbols: false);
        $admin = Admin::create([
            'user' => $cliente->email, 'nome' => $cliente->nome, 'cognome' => $cliente->cognome,
            'email' => $cliente->email, 'cellulare' => $cliente->telefono,
            'password' => $password, 'user_type' => 3, 'attivo' => 'Y', 'id_cliente' => $cliente->id,
        ]);

        return [$admin, $password];
    }

    /** Come Yii (sendEmailAccessi). Ritorna il testo da aggiungere al messaggio di esito. */
    private function inviaCredenziali(array $account): string
    {
        [$admin, $password] = $account;

        try {
            Mail::to($admin->email, trim($admin->nome.' '.$admin->cognome) ?: null)->send(new NotificaHtml(
                'Area Riservata',
                view('emails.credenziali-accesso', ['nome' => trim($admin->nome.' '.$admin->cognome), 'utente' => $admin->user, 'password' => $password])->render(),
                ['etichetta' => 'Accedi all\'area riservata', 'url' => route('login')],
            ));
        } catch (\Throwable $e) {
            report($e);

            return " Account creato, ma l'email con le credenziali non è stata inviata: usa «Password dimenticata?» dal login per generarne una nuova.";
        }

        return " Credenziali inviate a {$admin->email}.";
    }

    /** Come Yii (Clienti::removeRelated). */
    public function elimina(int $id): void
    {
        $this->soloStaff();
        $cliente = $this->clientiVisibili()->findOrFail($id);

        DB::connection('mysql')->transaction(function () use ($cliente) {
            DB::connection('mysql')->table('tc_documenti')->where('id_tipo', $cliente->id)->where('tipo', 'C')->delete();

            foreach (['tc_turni', 'tc_sedi', 'tc_postazioni', 'tc_turni_step', 'tc_admin', 'tc_operatori_preferiti', 'tc_operatori_escludi'] as $tabella) {
                DB::connection('mysql')->table($tabella)->where('id_cliente', $cliente->id)->delete();
            }

            $cliente->delete();
        });
    }

    public function render()
    {
        $clienti = $this->clientiVisibili()->with(['comune', 'provincia'])->withCount('sedi')
            ->when($this->cerca, fn ($q) => $q->where(fn ($w) => $w->where('ragione_sociale', 'like', "%{$this->cerca}%")->orWhereHas('comune', fn ($c) => $c->where('comune', 'like', "%{$this->cerca}%"))))
            ->orderBy('ragione_sociale')->paginate(10);

        return view('livewire.clienti', [
            'clienti' => $clienti,
            'province' => Provincia::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'operatori' => $this->modal ? Admin::where('user_type', 4)->where('attivo', 'Y')->orderBy('cognome')->orderBy('nome')->get(['id', 'nome', 'cognome']) : collect(),
            'documenti' => $this->modal && $this->clienteId ? Documento::where('tipo', 'C')->where('id_tipo', $this->clienteId)->orderByDesc('id')->get() : collect(),
            'comuni' => Comune::where(fn ($q) => $q->where('id_provincia', $this->f['provincia_id'] ?? 0 ?: 0)->orWhere('id', $this->f['citta_id'] ?? 0 ?: 0))->orderBy('comune')->get(['id', 'comune']),
        ]);
    }
}

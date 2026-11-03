<?php

namespace App\Livewire;

use App\Enums\Ruolo;
use App\Models\Legacy\Certificato;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Documento;
use App\Models\Legacy\Materiale;
use App\Models\Legacy\Provincia;
use App\Models\Legacy\TipoMateriale;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Operatori e utenti')]
class Operatori extends Component
{
    use WithFileUploads, WithPagination;

    private const ICONE = ['png' => 'file-image', 'jpg' => 'file-image', 'jpeg' => 'file-image', 'pdf' => 'file-pdf', 'doc' => 'file-word', 'docx' => 'file-word', 'zip' => 'file-archive', 'xls' => 'file-excel', 'xlsx' => 'file-excel', 'mp4' => 'file-audio'];

    private const ESTENSIONI = 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx,mp4,zip';

    public string $cerca = '';

    #[Url(as: 'form')]
    public bool $editing = false;

    #[Url]
    public string $tab = 'dati';

    public $avatar;

    public array $formazione = [];

    public $fileFormazione;

    public array $attestato = [];

    public $fileAttestato;

    public array $materiale = [];

    #[Url(as: 'id')]
    public ?int $userId = null;

    public array $f = [];

    public function updatingCerca(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'f.name' => 'required|string|max:30',
            'f.cognome' => 'required|string|max:30',
            'f.email' => ['required', 'email', 'max:40', Rule::unique('tc_admin', 'email')->ignore($this->userId)],
            'f.cellulare' => 'nullable|string|max:20',
            'f.ruolo' => ['required', Rule::in(array_column(Ruolo::cases(), 'value'))],
            'f.cliente_id' => 'nullable|required_if:f.ruolo,cliente|exists:tc_clienti,id',
            'f.codice' => 'nullable|required_if:f.ruolo,operatore|string|max:255',
            'f.costo_orario' => 'nullable|numeric|min:0',
            'f.colore' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'f.data_nascita' => 'nullable|string|max:10',
            'f.luogo_nascita' => 'nullable|string|max:255',
            'f.indirizzo' => 'nullable|string|max:255',
            'f.provincia' => 'nullable|integer|exists:_provincia,id',
            'f.iban' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
            'f.password' => ['nullable', 'string', 'min:8', 'max:40'],
        ];
    }

    /** Dopo un refresh il form riapre lo stesso record (stato preso dall'URL). */
    public function mount(): void
    {
        if (! $this->editing) {
            return;
        }

        $tab = in_array($this->tab, ['dati', 'formazioni', 'attestati', 'materiali', 'documenti', 'operatori'], true) ? $this->tab : 'dati';
        $id = $this->userId;

        try {
            $id ? $this->modifica($id) : $this->nuovo();
            $this->tab = $tab;
        } catch (ModelNotFoundException) {
            $this->editing = false;
            $this->userId = null;
        }
    }

    public function nuovo(): void
    {
        $this->userId = null;
        $this->f = ['name' => '', 'cognome' => '', 'email' => '', 'cellulare' => '', 'ruolo' => 'operatore', 'cliente_id' => '', 'codice' => '', 'costo_orario' => '', 'password' => '', 'attivo' => true, 'colore' => '#1d3e67', 'data_nascita' => '', 'luogo_nascita' => '', 'indirizzo' => '', 'provincia' => '', 'iban' => ''];
        $this->apriForm();
    }

    public function modifica(int $id): void
    {
        $u = $this->query()->findOrFail($id);
        $this->userId = $u->id;
        $this->f = [
            'name' => $u->name, 'cognome' => $u->cognome ?? '', 'email' => $u->email, 'cellulare' => $u->cellulare ?? '',
            'ruolo' => $u->ruolo->value, 'cliente_id' => $u->cliente_id ?? '', 'codice' => $u->codice ?? '',
            'costo_orario' => $u->costo_orario ?? '', 'password' => '', 'attivo' => $u->attivo,
            'colore' => $u->colore ?: '#1d3e67', 'data_nascita' => $u->data_nascita ?? '', 'luogo_nascita' => $u->luogo_nascita ?? '',
            'indirizzo' => $u->indirizzo ?? '', 'provincia' => $u->provincia ? (string) $u->provincia : '', 'iban' => $u->iban ?? '',
        ];
        $this->apriForm();
    }

    private function apriForm(): void
    {
        $this->reset('tab', 'avatar', 'formazione', 'fileFormazione', 'attestato', 'fileAttestato', 'materiale');
        $this->resetValidation();
        $this->editing = true;
    }

    public function salva(): void
    {
        $this->validate();
        $this->autorizzaRuolo($this->f['ruolo']);

        $dati = collect($this->f)->except('password')->map(fn ($v) => $v === '' ? null : $v)->all();
        $dati['colore'] ??= '#1d3e67';
        $dati['attivo'] = (bool) ($this->f['attivo'] ?? true);
        if ($this->f['ruolo'] !== 'cliente') {
            $dati['cliente_id'] = null;
        }

        if ($this->userId) {
            $u = $this->query()->findOrFail($this->userId);
            $u->update($dati + ($this->f['password'] ? ['password' => $this->f['password']] : []));
        } else {
            $u = User::create($dati + ['password' => $this->f['password'] ?: Str::password(8, symbols: false)]);
        }

        if ($this->avatar) {
            $nome = 'avatar_'.$u->id.'.'.strtolower($this->avatar->getClientOriginalExtension());
            Storage::putFileAs(config('filesystems.private_paths.avatars'), $this->avatar, $nome);
            $u->update(['avatar' => $nome]);
        }

        $this->editing = false;
    }

    public function aggiungiFormazione(): void
    {
        $this->validate([
            'formazione.inizio' => 'nullable|date',
            'formazione.fine' => 'nullable|date|after_or_equal:formazione.inizio',
            'formazione.descrizione' => 'required|string|max:255',
            'fileFormazione' => 'required|file|max:10240|mimes:'.self::ESTENSIONI,
        ], [], ['formazione.descrizione' => 'titolo', 'fileFormazione' => 'file']);

        $this->salvaDocumento($this->fileFormazione, 'Formazione', [
            'formazione' => 'Y', 'descrizione' => $this->formazione['descrizione'],
            'inizio' => $this->formazione['inizio'] ?? null ?: null, 'fine' => $this->formazione['fine'] ?? null ?: null,
        ]);
        $this->reset('formazione', 'fileFormazione');
    }

    public function aggiungiAttestato(): void
    {
        $this->validate([
            'attestato.tipologia' => 'required|exists:_certificati,id',
            'attestato.descrizione' => 'nullable|string|max:255',
            'attestato.scadenza' => 'nullable|date',
            'fileAttestato' => 'required|file|max:10240|mimes:'.self::ESTENSIONI,
        ], [], ['attestato.tipologia' => 'tipo documento', 'fileAttestato' => 'file']);

        $this->salvaDocumento($this->fileAttestato, 'Documento', [
            'formazione' => 'N', 'tipologia' => $this->attestato['tipologia'],
            'descrizione' => $this->attestato['descrizione'] ?? '', 'scadenza' => $this->attestato['scadenza'] ?? null ?: null,
        ]);
        $this->reset('attestato', 'fileAttestato');
    }

    /** Come Yii (_geFiles): file `{Tipo}_{nome}_{cognome}-{id}_{time}.ext` in Operatore_{id}. */
    private function salvaDocumento($file, string $prefisso, array $dati): void
    {
        $operatore = $this->operatoreCorrente();
        $estensione = strtolower($file->getClientOriginalExtension());
        $documento = new Documento($dati + ['id_tipo' => $operatore->id, 'tipo' => 'O', 'admin' => 'N', 'icona' => self::ICONE[$estensione] ?? 'file-alt']);
        $documento->file = $prefisso.'_'.$operatore->nome.'_'.$operatore->cognome.'-'.$operatore->id.'_'.time().'.'.$estensione;

        Storage::putFileAs(dirname($documento->percorso()), $file, basename($documento->percorso()));
        $documento->save();
    }

    public function aggiornaScadenza(int $id, ?string $scadenza): void
    {
        validator(['scadenza' => $scadenza ?: null], ['scadenza' => 'nullable|date'])->validate();

        Documento::where('tipo', 'O')->where('id_tipo', $this->operatoreCorrente()->id)->where('formazione', 'N')
            ->findOrFail($id)->update(['scadenza' => $scadenza ?: null]);
    }

    public function aggiornaTipologia(int $id, string $tipologia): void
    {
        validator(['tipologia' => $tipologia], ['tipologia' => 'required|exists:_certificati,id'])->validate();

        Documento::where('tipo', 'O')->where('id_tipo', $this->operatoreCorrente()->id)->where('formazione', 'N')
            ->findOrFail($id)->update(['tipologia' => $tipologia]);
    }

    public function eliminaDocumento(int $id): void
    {
        $documento = Documento::where('tipo', 'O')->where('id_tipo', $this->operatoreCorrente()->id)->findOrFail($id);
        Storage::delete($documento->percorso());
        $documento->delete();
    }

    public function aggiungiMateriale(): void
    {
        $this->validate([
            'materiale.id_materiale' => 'required|exists:_materiali,id',
            'materiale.descrizione' => 'nullable|string|max:255',
            'materiale.taglia' => 'nullable|string|max:255',
            'materiale.riconsegna' => 'nullable|date',
        ], [], ['materiale.id_materiale' => 'materiale']);

        Materiale::create([
            'id_operatore' => $this->operatoreCorrente()->id,
            'id_materiale' => $this->materiale['id_materiale'],
            'descrizione' => $this->materiale['descrizione'] ?? null,
            'taglia' => $this->materiale['taglia'] ?? null,
            'riconsegna' => $this->materiale['riconsegna'] ?? null ?: null,
            'consegnato' => ! empty($this->materiale['consegnato']) ? 'Y' : 'N',
        ]);
        $this->reset('materiale');
    }

    public function consegnaMateriale(int $id): void
    {
        $m = Materiale::where('id_operatore', $this->operatoreCorrente()->id)->findOrFail($id);
        $m->update(['consegnato' => $m->consegnato ? 'N' : 'Y']);
    }

    public function eliminaMateriale(int $id): void
    {
        Materiale::where('id_operatore', $this->operatoreCorrente()->id)->findOrFail($id)->delete();
    }

    private function operatoreCorrente(): User
    {
        abort_unless($this->userId, 404);

        return $this->query()->findOrFail($this->userId);
    }

    public function attivaDisattiva(int $id): void
    {
        $u = $this->query()->findOrFail($id);
        abort_if($u->id === auth()->id(), 403);
        $u->update(['attivo' => ! $u->attivo]);
    }

    /** Solo il super admin può creare/modificare amministratori. */
    private function autorizzaRuolo(string $ruolo): void
    {
        abort_if(in_array($ruolo, ['admin', 'super_admin']) && auth()->user()->ruolo !== Ruolo::SuperAdmin, 403);
    }

    private function query()
    {
        return User::query()->where('id', '!=', 1)->when(auth()->user()->ruolo !== Ruolo::SuperAdmin, fn ($q) => $q->whereNotIn('user_type', [1, 2]));
    }

    public function render()
    {
        $utenti = $this->query()->with('cliente')
            ->when($this->cerca, fn ($q) => $q->where(fn ($w) => $w->where('nome', 'like', "%{$this->cerca}%")->orWhere('cognome', 'like', "%{$this->cerca}%")->orWhere('email', 'like', "%{$this->cerca}%")))
            ->orderBy('cognome')->orderBy('nome')->paginate(10);

        $conSezioni = $this->editing && $this->userId && ($this->f['ruolo'] ?? '') === 'operatore';

        return view('livewire.operatori', [
            'utenti' => $utenti,
            'province' => Provincia::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'formazioni' => $conSezioni ? Documento::where('tipo', 'O')->where('id_tipo', $this->userId)->where('formazione', 'Y')->orderByDesc('id')->get() : collect(),
            'attestati' => $conSezioni ? Documento::with('certificato')->where('tipo', 'O')->where('id_tipo', $this->userId)->where('formazione', 'N')->orderByDesc('id')->get() : collect(),
            'materiali' => $conSezioni ? Materiale::with('tipo')->where('id_operatore', $this->userId)->orderByDesc('id')->get() : collect(),
            'tipiCertificato' => $conSezioni ? Certificato::orderBy('nome')->get() : collect(),
            'tipiMateriale' => $conSezioni ? TipoMateriale::orderBy('nome')->get() : collect(),
            'clienti' => Cliente::orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Enums\Ruolo;
use App\Models\Legacy\Certificato;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Documento;
use App\Models\Legacy\Materiale;
use App\Models\Legacy\Messaggio;
use App\Models\Legacy\Provincia;
use App\Models\Legacy\TipoMateriale;
use App\Models\Legacy\Tracking;
use App\Models\User;
use App\Services\Comunicazioni;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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

    public ?int $eliminaId = null;

    public string $conferma = '';

    #[Url(as: 'form')]
    public bool $editing = false;

    #[Url]
    public string $tab = 'dati';

    public $avatar;

    public array $formazione = [];

    public $fileFormazione;

    /** @var array<int, array{inizio: string, fine: string, descrizione: string}> */
    public array $righeFormazioni = [];

    /** @var array<int, mixed> file sostitutivi per riga, indicizzati per id documento */
    public array $fileRigheFormazioni = [];

    public array $attestato = [];

    public $fileAttestato;

    public array $materiale = [];

    /** @var array<int, array{id_materiale: string, descrizione: string, taglia: string, riconsegna: string, consegnato: bool}> */
    public array $righeMateriali = [];

    #[Url(as: 'id')]
    public ?int $userId = null;

    public array $f = [];

    /** Difesa in profondità: ogni richiesta Livewire, non solo la rotta, richiede ruolo staff. */
    public function boot(): void
    {
        abort_unless(auth()->user()?->isStaff(), 403);
    }

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
            'f.cellulare' => 'required|regex:/^\s*[+-]?\d+\s*$/|max:20',
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
        Tracking::registra('Visualizzazione elenco operatori ');

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
        $this->caricaMateriali();
        $this->caricaFormazioni();
    }

    private function apriForm(): void
    {
        $this->reset('tab', 'avatar', 'formazione', 'fileFormazione', 'righeFormazioni', 'fileRigheFormazioni', 'attestato', 'fileAttestato', 'materiale', 'righeMateriali');
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

        $password = null;

        if ($this->userId) {
            $u = $this->query()->findOrFail($this->userId);
            $u->update($dati + ($this->f['password'] ? ['password' => $this->f['password']] : []));
            Tracking::registra('Modifica operatore '.$u->user);
        } else {
            $password = $this->f['password'] ?: Str::password(8, symbols: false);
            $u = User::create($dati + ['password' => $password]);
            Tracking::registra(($this->f['ruolo'] === 'operatore' ? 'Nuovo operatore ' : 'Nuovo utente ').$u->user);
        }

        if ($this->avatar) {
            $nome = 'avatar_'.$u->id.'.'.strtolower($this->avatar->getClientOriginalExtension());
            $this->avatar->storeAs(config('filesystems.private_paths.avatars'), $nome, ['disk' => config('filesystems.files_disk')]);
            $u->update(['avatar' => $nome]);
        }

        $this->editing = false;

        // Come Yii (sendEmailAccessi): alla creazione l'utente riceve le credenziali provvisorie.
        if ($password !== null) {
            $inviata = app(Comunicazioni::class)->inviaCredenziali($u->nome_completo, (string) $u->user, (string) $u->email, $password);
            session()->flash('esito', $inviata
                ? "Utente creato. Credenziali inviate a {$u->email}."
                : 'Utente creato, ma l\'email con le credenziali non è stata inviata: comunica la password manualmente.');
        }
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
        $this->caricaFormazioni();
    }

    public function salvaFormazione(int $id): void
    {
        $this->validate([
            "righeFormazioni.$id.inizio" => 'nullable|date',
            "righeFormazioni.$id.fine" => "nullable|date|after_or_equal:righeFormazioni.$id.inizio",
            "righeFormazioni.$id.descrizione" => 'required|string|max:255',
            "fileRigheFormazioni.$id" => 'nullable|file|max:10240|mimes:'.self::ESTENSIONI,
        ], [], ["righeFormazioni.$id.descrizione" => 'titolo', "righeFormazioni.$id.inizio" => 'data inizio', "righeFormazioni.$id.fine" => 'data fine', "fileRigheFormazioni.$id" => 'file']);

        $operatore = $this->operatoreCorrente();
        $documento = Documento::where('tipo', 'O')->where('id_tipo', $operatore->id)->where('formazione', 'Y')->findOrFail($id);
        $riga = $this->righeFormazioni[$id];
        $dati = [
            'descrizione' => $riga['descrizione'],
            'inizio' => ($riga['inizio'] ?? null) ?: null,
            'fine' => ($riga['fine'] ?? null) ?: null,
        ];

        $file = $this->fileRigheFormazioni[$id] ?? null;
        if ($file) {
            $disco = Storage::disk(config('filesystems.files_disk'));
            $vecchioPercorso = $documento->percorso();
            $estensione = strtolower($file->getClientOriginalExtension());
            $documento->file = 'Formazione_'.$operatore->nome.'_'.$operatore->cognome.'-'.$operatore->id.'_'.time().'.'.$estensione;
            $file->storeAs(dirname($documento->percorso()), basename($documento->percorso()), ['disk' => config('filesystems.files_disk')]);
            $disco->delete($vecchioPercorso);
            $dati += ['file' => $documento->file, 'icona' => self::ICONE[$estensione] ?? 'file-alt'];
        }

        $documento->update($dati);
        unset($this->fileRigheFormazioni[$id]);
        $this->caricaFormazioni();
    }

    private function caricaFormazioni(): void
    {
        $this->righeFormazioni = Documento::where('tipo', 'O')->where('id_tipo', $this->operatoreCorrente()->id)->where('formazione', 'Y')->orderByDesc('id')->get()
            ->mapWithKeys(fn (Documento $d) => [$d->id => [
                'inizio' => $d->inizio?->format('Y-m-d') ?? '',
                'fine' => $d->fine?->format('Y-m-d') ?? '',
                'descrizione' => $d->descrizione ?? '',
            ]])->all();
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

        $file->storeAs(dirname($documento->percorso()), basename($documento->percorso()), ['disk' => config('filesystems.files_disk')]);
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
        Storage::disk(config('filesystems.files_disk'))->delete($documento->percorso());
        $documento->delete();
        $this->caricaFormazioni();
    }

    public function aggiungiMateriale(): void
    {
        $this->validate([
            'materiale.id_materiale' => 'required|exists:_materiali,id',
            'materiale.descrizione' => 'nullable|string|max:255',
            'materiale.taglia' => 'nullable|string|max:255',
            'materiale.riconsegna' => 'nullable|date',
        ], [], ['materiale.id_materiale' => 'materiale']);

        Materiale::create($this->datiMateriale($this->materiale) + ['id_operatore' => $this->operatoreCorrente()->id]);

        $this->reset('materiale');
        $this->caricaMateriali();
    }

    public function salvaMateriale(int $id): void
    {
        $this->validate([
            "righeMateriali.$id.id_materiale" => 'required|exists:_materiali,id',
            "righeMateriali.$id.descrizione" => 'nullable|string|max:255',
            "righeMateriali.$id.taglia" => 'nullable|string|max:255',
            "righeMateriali.$id.riconsegna" => 'nullable|date',
        ], [], ["righeMateriali.$id.id_materiale" => 'materiale']);

        Materiale::where('id_operatore', $this->operatoreCorrente()->id)->findOrFail($id)->update($this->datiMateriale($this->righeMateriali[$id]));
        $this->caricaMateriali();
    }

    public function eliminaMateriale(int $id): void
    {
        Materiale::where('id_operatore', $this->operatoreCorrente()->id)->findOrFail($id)->delete();
        $this->caricaMateriali();
    }

    private function caricaMateriali(): void
    {
        $this->righeMateriali = Materiale::where('id_operatore', $this->operatoreCorrente()->id)->orderByDesc('id')->get()
            ->mapWithKeys(fn (Materiale $m) => [$m->id => [
                'id_materiale' => (string) $m->id_materiale,
                'descrizione' => $m->descrizione ?? '',
                'taglia' => $m->taglia ?? '',
                'riconsegna' => $m->riconsegna?->format('Y-m-d') ?? '',
                'consegnato' => (bool) $m->consegnato,
            ]])->all();
    }

    /** @param  array<string, mixed>  $riga */
    private function datiMateriale(array $riga): array
    {
        return [
            'id_materiale' => $riga['id_materiale'],
            'descrizione' => ($riga['descrizione'] ?? null) ?: null,
            'taglia' => ($riga['taglia'] ?? null) ?: null,
            'riconsegna' => ($riga['riconsegna'] ?? null) ?: null,
            'consegnato' => ! empty($riga['consegnato']) ? 'Y' : 'N',
        ];
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
        Tracking::registra(($u->attivo ? 'Abilitazione ' : 'Disabilitazione ').' elenco utenti '.$u->user);
    }

    public function richiediEliminazione(int $id): void
    {
        $utente = $this->query()->findOrFail($id);
        abort_if($utente->id === auth()->id(), 403);
        $this->reset('conferma');
        $this->resetErrorBag();
        $this->eliminaId = $id;
    }

    private function confermaValida(string $atteso): bool
    {
        $normalizza = fn (string $v): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v)));

        return $normalizza($this->conferma) === $normalizza($atteso);
    }

    public function annullaEliminazione(): void
    {
        $this->reset('eliminaId', 'conferma');
        $this->resetErrorBag();
    }

    /** Come attivaDisattiva ma definitiva: rimuove anche i dati collegati e i file. */
    public function elimina(): void
    {
        abort_unless($this->eliminaId, 404);
        $utente = $this->query()->findOrFail($this->eliminaId);
        abort_if($utente->id === auth()->id(), 403);

        if (! $this->confermaValida('conferma')) {
            $this->addError('conferma', 'Il testo non coincide: eliminazione non eseguita.');

            return;
        }

        if ($this->storicoTurni($utente->id) > 0) {
            $this->addError('conferma', 'Questo utente ha turni registrati: non può essere eliminato. Disattivalo.');

            return;
        }

        // Niente transazione: tabelle legacy MyISAM e GTID.
        $db = DB::connection('mysql');
        $db->table('tc_documenti')->where('id_tipo', $utente->id)->where('tipo', 'O')->delete();
        foreach (['tc_materiali', 'tc_operatori_preferiti', 'tc_operatori_escludi', 'tc_documenti_view', 'tc_bacheca_view'] as $tabella) {
            $db->table($tabella)->where('id_operatore', $utente->id)->delete();
        }

        $disco = Storage::disk(config('filesystems.files_disk'));
        $disco->deleteDirectory(trim(config('filesystems.private_paths.documenti'), '/').'/Operatore_'.$utente->id);
        if ($utente->foto_path) {
            $disco->delete(trim(config('filesystems.private_paths.avatars'), '/').'/'.basename($utente->foto_path));
        }

        $utente->delete();
        Tracking::registra('Cancellazione utente '.$utente->user);

        $this->annullaEliminazione();
        session()->flash('esito', "Utente {$utente->nome_completo} eliminato.");
    }

    private function storicoTurni(int $id): int
    {
        return DB::connection('mysql')->table('tc_turni')->where('id_operatore', $id)->count()
            + DB::connection('mysql')->table('tc_gda')->where('id_operatore', $id)->count();
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
        $daEliminare = $this->eliminaId ? $this->query()->find($this->eliminaId) : null;
        $riepilogo = $daEliminare ? [
            'documenti' => DB::connection('mysql')->table('tc_documenti')->where('id_tipo', $daEliminare->id)->where('tipo', 'O')->count(),
            'materiali' => DB::connection('mysql')->table('tc_materiali')->where('id_operatore', $daEliminare->id)->count(),
            'turni' => $this->storicoTurni($daEliminare->id),
        ] : [];

        $utenti = $this->query()->with('cliente')
            ->when($this->cerca, fn ($q) => $q->where(fn ($w) => $w->where('nome', 'like', "%{$this->cerca}%")->orWhere('cognome', 'like', "%{$this->cerca}%")->orWhere('email', 'like', "%{$this->cerca}%")))
            ->orderBy('cognome')->orderBy('nome')->paginate(10);

        $conSezioni = $this->editing && $this->userId && ($this->f['ruolo'] ?? '') === 'operatore';

        return view('livewire.operatori', [
            'utenti' => $utenti,
            'daEliminare' => $daEliminare,
            'riepilogo' => $riepilogo,
            'province' => Provincia::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'attestati' => $conSezioni ? Documento::with('certificato')->where('tipo', 'O')->where('id_tipo', $this->userId)->where('formazione', 'N')->orderByDesc('id')->get() : collect(),
            'limitePromemoria' => today()->addDays((int) Messaggio::find(5)?->giorni),
            'tipiCertificato' => $conSezioni ? Certificato::orderBy('nome')->get() : collect(),
            'tipiMateriale' => $conSezioni ? TipoMateriale::orderBy('nome')->get() : collect(),
            'clienti' => Cliente::orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
        ]);
    }
}

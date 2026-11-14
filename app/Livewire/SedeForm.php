<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Codice;
use App\Models\Legacy\Comune;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Provincia;
use App\Models\Legacy\Sede;
use App\Models\Legacy\Tracking;
use App\Services\Geocoding;
use App\Services\QrSede;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use LogicException;
use Throwable;

#[Layout('layouts::app')]
class SedeForm extends Component
{
    use ConAccesso, WithFileUploads;

    public ?int $sedeId = null;

    public string $nomeCliente = '';

    public string $nomeSede = '';

    public string $codiceSedeId = '';

    public ?string $fotoSede = null;

    public $foto;

    public array $f = [];

    /** @var array<string, array{id: ?int, codice_id: string, titolo: string, passaggi: mixed, minuti: mixed, info: string, file: ?string}> */
    public array $postazioni = [];

    public array $immaginiPostazioni = [];

    /** @var array<int, int> */
    public array $postazioniEliminate = [];

    public array $ore = [1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => ''];

    private const CAMPI = ['cliente_id', 'descrizione', 'indirizzo', 'cap', 'citta', 'provincia', 'telefono', 'email', 'note', 'lat', 'lng', 'distanza_max', 'tariffa', 'colore', 'pos', 'inizio_contratto', 'fine_contratto'];

    protected function rules(): array
    {
        return [
            'f.cliente_id' => 'required|exists:tc_clienti,id',
            'f.descrizione' => 'required|string|max:255',
            'f.indirizzo' => 'required|string|max:200',
            'f.citta' => 'required|string|max:255',
            'f.provincia' => 'required|string|size:2',
            'f.telefono' => 'nullable|string|max:16',
            'f.cap' => 'nullable|string|max:5',
            'f.email' => 'nullable|email|max:255',
            'f.lat' => 'nullable|numeric|between:-90,90',
            'f.lng' => 'nullable|numeric|between:-180,180',
            'f.distanza_max' => 'required|integer|min:10|max:5000',
            'f.tariffa' => 'nullable|numeric|min:0',
            'f.colore' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'f.pos' => 'nullable|string|max:65535',
            'f.inizio_contratto' => 'nullable|date',
            'f.fine_contratto' => 'nullable|date|after_or_equal:f.inizio_contratto',
            'codiceSedeId' => 'required|string',
            'foto' => 'nullable|image|max:4096',
            'postazioni.*.titolo' => 'required|string|max:255',
            'postazioni.*.codice_id' => 'required|string',
            'postazioni.*.passaggi' => 'nullable|integer|min:0|max:1000',
            'postazioni.*.minuti' => 'nullable|integer|min:0|max:1440',
            'postazioni.*.info' => 'nullable|string|max:65535',
            'immaginiPostazioni.*' => 'nullable|image|max:4096',
            'ore.*' => 'nullable|integer|min:0|max:24',
        ];
    }

    protected function messages(): array
    {
        return [
            'postazioni.*.titolo.required' => 'Indica il titolo della postazione.',
            'postazioni.*.titolo.max' => 'Il titolo della postazione può avere al massimo 255 caratteri.',
            'postazioni.*.codice_id.required' => 'Associa un QR code alla postazione.',
            'postazioni.*.passaggi.integer' => 'Il numero di passaggi deve essere un numero intero.',
            'postazioni.*.passaggi.min' => 'Il numero di passaggi non può essere negativo.',
            'postazioni.*.passaggi.max' => 'Il numero di passaggi non può superare 1000.',
            'postazioni.*.minuti.integer' => 'I minuti di passaggio devono essere un numero intero.',
            'postazioni.*.minuti.min' => 'I minuti di passaggio non possono essere negativi.',
            'postazioni.*.minuti.max' => 'I minuti di passaggio non possono superare 1440.',
            'postazioni.*.info.max' => 'Le informazioni della postazione sono troppo lunghe.',
            'immaginiPostazioni.*.image' => 'Carica un\'immagine per la postazione.',
            'immaginiPostazioni.*.max' => 'L\'immagine della postazione non può superare 4 MB.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'postazioni.*.titolo' => 'titolo della postazione',
            'postazioni.*.codice_id' => 'QR code della postazione',
            'postazioni.*.passaggi' => 'numero di passaggi',
            'postazioni.*.minuti' => 'minuti di passaggio',
            'postazioni.*.info' => 'informazioni della postazione',
            'immaginiPostazioni.*' => 'immagine della postazione',
        ];
    }

    public function mount(?int $sede = null): void
    {
        $this->soloStaff();

        if ($sede === null) {
            $this->f = array_fill_keys(self::CAMPI, '');
            $this->f['distanza_max'] = 100;
            $this->f['cliente_id'] = request()->integer('cliente') ?: '';
            $this->f['colore'] = '#1d3e67';

            return;
        }

        $s = $this->sediVisibili()->with(['cliente', 'postazioni'])->findOrFail($sede);
        $this->sedeId = $s->id;
        $this->nomeCliente = (string) $s->cliente?->ragione_sociale;
        $this->nomeSede = $s->descrizione;
        $this->f = collect(self::CAMPI)->mapWithKeys(fn ($k) => [$k => $s->$k ?? ''])->all();
        $this->f['cliente_id'] = $s->id_cliente;
        $this->f['citta'] = $s->comune?->comune ?? '';
        $this->f['provincia'] = $s->provincia?->sigla ?? '';
        $this->f['tariffa'] = $s->tariffa ?? '';
        $this->f['colore'] = $s->colore ?: '#1d3e67';
        $this->f['pos'] = (string) $s->pos;
        $this->f['inizio_contratto'] = $s->inizio_contratto?->format('Y-m-d') ?? '';
        $this->f['fine_contratto'] = $s->fine_contratto?->format('Y-m-d') ?? '';
        $this->codiceSedeId = (string) $s->id_codice;
        $this->fotoSede = $s->foto;
        $this->postazioni = $s->postazioni->sortBy('id')->mapWithKeys(fn (Postazione $p) => ['p'.$p->id => [
            'id' => $p->id,
            'codice_id' => (string) $p->id_codice,
            'titolo' => (string) $p->titolo,
            'passaggi' => $p->passaggi ?? '',
            'minuti' => $p->minuti ?? '',
            'info' => (string) $p->info,
            'file' => $p->file,
        ]])->all();
        $this->ore = collect(range(1, 7))->mapWithKeys(fn ($g) => [$g => $s->ore_richieste[$g] ?? ''])->all();
    }

    public function aggiungiPostazione(): void
    {
        $this->postazioni['n'.Str::random(6)] = ['id' => null, 'codice_id' => '', 'titolo' => '', 'passaggi' => '', 'minuti' => '', 'info' => '', 'file' => null];
    }

    public function rimuoviPostazione(string $chiave): void
    {
        $id = $this->postazioni[$chiave]['id'] ?? null;
        if ($id) {
            $this->postazioniEliminate[] = (int) $id;
        }
        unset($this->postazioni[$chiave], $this->immaginiPostazioni[$chiave]);
    }

    public function salva()
    {
        $this->soloStaff();
        try {
            $this->validate();
            $this->validaCodiciSelezionati();
        } catch (ValidationException $e) {
            $this->dispatch('tab-errore', tab: $this->tabConErrori(array_keys($e->errors())));

            throw $e;
        }
        // Come nel vecchio: il cliente di una sede esistente non cambia (le cartelle sono per cliente).
        if ($this->sedeId) {
            $this->f['cliente_id'] = $this->sediVisibili()->findOrFail($this->sedeId)->id_cliente;
        }
        $this->clientiVisibili()->findOrFail($this->f['cliente_id']);

        $sigla = strtoupper($this->f['provincia']);
        $comune = Comune::where('comune', $this->f['citta'])->where('sigla_provincia', $sigla)->first()
            ?? Comune::where('comune', $this->f['citta'])->first();

        if (! $comune) {
            $this->addError('f.citta', 'Comune non trovato in anagrafica.');

            return null;
        }

        $dati = [
            'id_cliente' => $this->f['cliente_id'],
            'descrizione' => $this->f['descrizione'],
            'indirizzo' => $this->f['indirizzo'],
            'cap' => $this->f['cap'] ?: null,
            'citta_id' => $comune->id,
            'provincia_id' => $comune->id_provincia ?: Provincia::where('sigla', $sigla)->value('id'),
            'telefono' => $this->f['telefono'] ?: null,
            'email' => $this->f['email'] ?: null,
            'note' => (string) $this->f['note'],
            'colore' => $this->f['colore'] ?: null,
            'pos' => $this->f['pos'] ?: null,
            'inizio_contratto' => $this->f['inizio_contratto'] ?: null,
            'fine_contratto' => $this->f['fine_contratto'] ?: null,
            'lat' => (float) $this->f['lat'],
            'lng' => (float) $this->f['lng'],
            'tariffa' => $this->f['tariffa'] === '' ? null : (float) str_replace(',', '.', (string) $this->f['tariffa']),
            'distanza' => (int) $this->f['distanza_max'],
        ];

        // Come _setCoordination di Yii: senza coordinate indicate le ricava dall'indirizzo.
        if (! $dati['lat'] && ! $dati['lng']) {
            $coordinate = app(Geocoding::class)->coordinate(trim($this->f['indirizzo'].' '.$this->f['cap'].' '.$comune->comune));
            $dati['lat'] = $coordinate['lat'] ?? 0.0;
            $dati['lng'] = $coordinate['lng'] ?? 0.0;
        }

        foreach ([1 => 'lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'] as $n => $colonna) {
            $dati[$colonna] = $this->ore[$n] === '' ? null : (int) $this->ore[$n];
        }

        $codiciGenerati = $this->preparaCodici();

        // Niente transazione: le tabelle legacy sono MyISAM e GTID vieta di mischiarle con tabelle transazionali.
        if ($this->sedeId) {
            $sede = $this->sediVisibili()->findOrFail($this->sedeId);
            $sede->update($dati);
        } else {
            $sede = Sede::create($dati);
        }

        $codiceSede = $this->associaCodiceSede($sede, $codiciGenerati['sede'] ?? null);
        $sede->update(['id_codice' => $codiceSede->id]);

        $this->salvaFoto($sede);
        $this->salvaPostazioni($sede, $codiciGenerati['postazioni'] ?? []);

        Tracking::registra(($this->sedeId ? 'Aggiornamento dati sede ' : 'Creazione sede ').$sede->descrizione);
        if (! $sede->lat && ! $sede->lng) {
            session()->flash('avviso', "Sede {$sede->descrizione} salvata senza coordinate: il controllo della distanza di scansione non funzionerà finché non le imposti.");
        }

        return $this->redirectRoute('sedi', ['cliente' => $this->f['cliente_id']], navigate: true);
    }

    private function salvaFoto(Sede $sede): void
    {
        if (! $this->foto) {
            return;
        }

        $nome = 'Postazione-principale-'.$sede->id.'-'.time().'.'.strtolower($this->foto->getClientOriginalExtension());
        $this->foto->storeAs($sede->cartella(), $nome, ['disk' => config('filesystems.files_disk')]);
        if ($sede->percorsoFoto()) {
            Storage::disk(config('filesystems.files_disk'))->delete($sede->percorsoFoto());
        }
        $sede->update(['foto' => $nome]);
    }

    /** @param array<string, string> $codiciGenerati */
    private function salvaPostazioni(Sede $sede, array $codiciGenerati): void
    {
        $daEliminare = $sede->postazioni()->whereIn('id', $this->postazioniEliminate)->get();
        $daEliminare->each(fn (Postazione $p) => Storage::disk(config('filesystems.files_disk'))->deleteDirectory($p->cartella()));
        $sede->postazioni()->whereIn('id', $daEliminare->modelKeys())->delete();

        foreach ($this->postazioni as $chiave => $dati) {
            $campi = [
                'id_cliente' => $sede->id_cliente,
                'titolo' => $dati['titolo'],
                'info' => $dati['info'] ?: null,
                'passaggi' => $dati['passaggi'] === '' ? null : (int) $dati['passaggi'],
                'minuti' => $dati['minuti'] === '' ? null : (int) $dati['minuti'],
            ];

            if ($dati['id']) {
                $postazione = $sede->postazioni()->findOrFail($dati['id']);
                $postazione->update($campi);
            } else {
                $postazione = $sede->postazioni()->create($campi);
            }

            $codice = $this->associaCodicePostazione($postazione, (string) $dati['codice_id'], $codiciGenerati[$chiave] ?? null);
            $postazione->update(['id_codice' => $codice->id]);

            if ($immagine = $this->immaginiPostazioni[$chiave] ?? null) {
                $nome = 'Postazione-'.$postazione->id.'-'.time().'.'.strtolower($immagine->getClientOriginalExtension());
                Storage::disk(config('filesystems.files_disk'))->deleteDirectory($postazione->cartella());
                $immagine->storeAs($postazione->cartella(), $nome, ['disk' => config('filesystems.files_disk')]);
                $postazione->update(['file' => $nome]);
            }
        }
    }

    private function validaCodiciSelezionati(): void
    {
        $sede = $this->sedeId
            ? $this->sediVisibili()->with('postazioni')->findOrFail($this->sedeId)
            : null;
        $scelte = [[
            'campo' => 'codiceSedeId',
            'valore' => $this->codiceSedeId,
            'corrente' => $sede?->id_codice,
            'sede' => $sede?->id,
            'postazione' => null,
            'tipo' => Codice::SEDE,
        ]];

        foreach ($this->postazioni as $chiave => $dati) {
            $postazione = $dati['id'] && $sede
                ? $sede->postazioni->firstWhere('id', (int) $dati['id'])
                : null;

            if ($dati['id'] && ! $postazione) {
                throw ValidationException::withMessages([
                    "postazioni.$chiave.id" => 'La postazione selezionata non appartiene a questa sede.',
                ]);
            }

            $scelte[] = [
                'campo' => "postazioni.$chiave.codice_id",
                'valore' => (string) $dati['codice_id'],
                'corrente' => $postazione?->id_codice,
                'sede' => $sede?->id,
                'postazione' => $postazione?->id,
                'tipo' => Codice::POSTAZIONE,
            ];
        }

        $errori = [];
        $codiciSelezionati = [];

        foreach ($scelte as $scelta) {
            $valore = $scelta['valore'];
            if ($valore === 'XX') {
                if (Codice::where('stato', Codice::CREATO)->exists()) {
                    $errori[$scelta['campo']][] = 'Non ci sono QR validi da generare per questa selezione.';
                }

                continue;
            }

            $codiceId = filter_var($valore, FILTER_VALIDATE_INT);
            $valido = $codiceId && Codice::query()
                ->whereKey($codiceId)
                ->where(function ($query) use ($codiceId, $scelta): void {
                    $query->where('stato', Codice::CREATO);

                    if ((int) $scelta['corrente'] === $codiceId && $scelta['sede']) {
                        $query->orWhere(function ($query) use ($scelta): void {
                            $query->where('tipo', $scelta['tipo'])
                                ->where('id_sede', $scelta['sede']);

                            if ($scelta['postazione']) {
                                $query->where('id_postazione', $scelta['postazione']);
                            } else {
                                $query->whereNull('id_postazione');
                            }
                        });
                    }
                })
                ->exists();

            if (! $valido) {
                $errori[$scelta['campo']][] = 'Seleziona un QR libero o quello già associato a questo elemento.';

                continue;
            }

            if (isset($codiciSelezionati[$codiceId])) {
                $messaggio = 'Lo stesso QR non può essere assegnato a più elementi.';
                $errori[$scelta['campo']][] = $messaggio;
                $errori[$codiciSelezionati[$codiceId]][] = $messaggio;

                continue;
            }

            $codiciSelezionati[$codiceId] = $scelta['campo'];
        }

        if ($errori !== []) {
            throw ValidationException::withMessages($errori);
        }
    }

    /** @return array{sede?: string, postazioni?: array<string, string>} */
    private function preparaCodici(): array
    {
        $qr = app(QrSede::class);
        $generati = [];
        $risultato = [];

        try {
            if ($this->codiceSedeId === 'XX') {
                $codice = Codice::prossimoCodice($generati);
                $qr->genera($codice);
                $generati[] = $codice;
                $risultato['sede'] = $codice;
            }

            foreach ($this->postazioni as $chiave => $dati) {
                if ($dati['codice_id'] !== 'XX') {
                    continue;
                }

                $codice = Codice::prossimoCodice($generati);
                $qr->genera($codice);
                $generati[] = $codice;
                $risultato['postazioni'][$chiave] = $codice;
            }
        } catch (Throwable $e) {
            foreach ($generati as $codice) {
                try {
                    Storage::disk(config('filesystems.files_disk'))->delete($qr->percorso($codice));
                } catch (Throwable $errorePulizia) {
                    report($errorePulizia);
                }
            }

            throw $e;
        }

        return $risultato;
    }

    private function associaCodiceSede(Sede $sede, ?string $codiceGenerato): Codice
    {
        if ($this->codiceSedeId === 'XX') {
            return Codice::nuovo(Codice::SEDE, $sede->id, null, $codiceGenerato ?? throw new LogicException('QR sede non preparato.'));
        }

        $codice = Codice::findOrFail((int) $this->codiceSedeId);
        $codice->update([
            'stato' => Codice::ATTIVO,
            'tipo' => Codice::SEDE,
            'id_sede' => $sede->id,
            'id_postazione' => null,
        ]);

        return $codice;
    }

    private function associaCodicePostazione(Postazione $postazione, string $codiceId, ?string $codiceGenerato): Codice
    {
        if ($codiceId === 'XX') {
            return Codice::nuovo(Codice::POSTAZIONE, $postazione->id_sede, $postazione->id, $codiceGenerato ?? throw new LogicException('QR postazione non preparato.'));
        }

        $codice = Codice::findOrFail((int) $codiceId);
        $codice->update([
            'stato' => Codice::ATTIVO,
            'tipo' => Codice::POSTAZIONE,
            'id_sede' => $postazione->id_sede,
            'id_postazione' => $postazione->id,
        ]);

        return $codice;
    }

    /**
     * @return array<int|string, string>
     */
    private function codiciSelezionabili(?int $codiceCorrente): array
    {
        $query = Codice::query()->where('stato', Codice::CREATO);

        if ($codiceCorrente) {
            $query->orWhereKey($codiceCorrente);
        }

        $risultati = $query->orderBy('codice')->get(['id', 'codice', 'stato']);
        $haCodiciLiberi = $risultati->contains('stato', Codice::CREATO);
        $codici = $risultati
            ->mapWithKeys(fn (Codice $codice) => [$codice->id => $codice->codice])
            ->all();

        if (! $haCodiciLiberi) {
            $codici['XX'] = 'CODICE DA GENERARE';
        }

        return $codici;
    }

    private function tabConErrori(array $campi): string
    {
        $campo = $campi[0] ?? '';

        if (in_array($campo, ['f.tariffa', 'f.pos', 'f.inizio_contratto', 'f.fine_contratto'], true) || str_starts_with($campo, 'ore.')) {
            return 'pos';
        }

        if (in_array($campo, ['f.indirizzo', 'f.cap', 'f.citta', 'f.provincia', 'f.lat', 'f.lng', 'f.distanza_max'], true)) {
            return 'posizione';
        }

        if (str_starts_with($campo, 'postazioni.') || str_starts_with($campo, 'immaginiPostazioni.')) {
            return 'postazioni';
        }

        return 'dati';
    }

    public function render()
    {
        $clienti = $this->clientiVisibili()->orderBy('ragione_sociale')->get(['id', 'ragione_sociale']);
        $nomeCliente = $clienti->firstWhere('id', (int) ($this->f['cliente_id'] ?? 0))?->ragione_sociale ?? $this->nomeCliente;
        $sede = $this->sedeId
            ? $this->sediVisibili()->with('postazioni')->findOrFail($this->sedeId)
            : null;
        $codiciPostazioni = collect($this->postazioni)->mapWithKeys(function (array $dati, string $chiave) use ($sede): array {
            $postazione = $dati['id'] && $sede
                ? $sede->postazioni->firstWhere('id', (int) $dati['id'])
                : null;

            return [$chiave => $this->codiciSelezionabili($postazione?->id_codice)];
        });

        return view('livewire.sede-form', [
            'clienti' => $clienti,
            'nomeCliente' => $nomeCliente,
            'codiciSede' => $this->codiciSelezionabili($sede?->id_codice),
            'codiciPostazioni' => $codiciPostazioni,
            'giorni' => [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'],
        ])->title($this->sedeId ? 'Modifica / Gestisci sede e postazioni - '.$nomeCliente.' - '.$this->nomeSede : 'Nuova sede'.($nomeCliente ? ' - '.$nomeCliente : ''));
    }
}

<?php

namespace App\Livewire;

use App\Models\Legacy\Codice;
use App\Models\User;
use App\Services\QrAziendale;
use App\Services\QrSede;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

#[Layout('layouts::app')]
#[Title('Generatore QR')]
class GeneratoreQr extends Component
{
    use WithFileUploads;

    public string $colore = '#000000';

    public string $titolo = '';

    public int $quantita = 1;

    public string $modalita = 'nuovo';

    public string $codiceEsistenteId = '';

    public $logo;

    /** @var array<int, array{id: int, codice: string}> */
    public array $appenaCreati = [];

    public string $codiceId = '';

    public function mount(): void
    {
        $this->autorizzaStaff();
    }

    public function generaLiberi(QrSede $qr): void
    {
        $this->autorizzaStaff();
        $dati = $this->validate(
            [
                'modalita' => ['required', 'in:nuovo,esistente'],
                'quantita' => ['exclude_unless:modalita,nuovo', 'required', 'integer', 'min:1', 'max:20'],
                'codiceEsistenteId' => ['exclude_unless:modalita,esistente', 'required'],
                'colore' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'titolo' => ['nullable', 'string', 'max:40'],
                'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            ],
            [
                'quantita.min' => 'Indica da 1 a 20 QR da generare.',
                'quantita.max' => 'Indica da 1 a 20 QR da generare.',
                'codiceEsistenteId.required' => 'Scegli un codice libero senza foto.',
                'titolo.max' => 'Il titolo può avere al massimo 40 caratteri.',
            ],
            [
                'quantita' => 'quantità',
                'codiceEsistenteId' => 'codice',
                'titolo' => 'titolo',
                'colore' => 'colore',
                'logo' => 'logo',
            ],
        );

        $titolo = trim(preg_replace('/\s+/u', ' ', $dati['titolo'] ?? '') ?? '');
        $contenutoLogo = $this->logo?->get();
        $creati = [];
        $riservati = [];

        try {
            if ($dati['modalita'] === 'esistente') {
                $creati[] = $this->salvaFotoSuCodiceEsistente($qr, $dati['colore'], $contenutoLogo, $titolo);
            } else {
                for ($i = 0; $i < $dati['quantita']; $i++) {
                    $creati[] = $this->creaCodiceLibero($qr, $riservati, $dati['colore'], $contenutoLogo, $titolo);
                    $riservati[] = $creati[array_key_last($creati)]->codice;
                }
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->annullaCodici($creati, $qr);

            throw $e;
        }

        $this->appenaCreati = array_map(fn (Codice $codice): array => [
            'id' => $codice->id,
            'codice' => $codice->codice,
        ], $creati);

        $primo = $creati[0]->codice;
        $ultimo = $creati[array_key_last($creati)]->codice;
        session()->flash('qr', $dati['modalita'] === 'esistente'
            ? "Foto salvata su {$primo}."
            : (count($creati) === 1
                ? "Creato {$primo}."
                : "Creati da {$primo} a {$ultimo}."));
    }

    public function render(QrAziendale $generatore, QrSede $qr)
    {
        $this->autorizzaStaff();
        $prossimo = Codice::prossimoCodice();
        $logoDataUri = $this->logo
            ? 'data:'.$this->logo->getMimeType().';base64,'.base64_encode($this->logo->get())
            : null;
        $codici = Codice::query()
            ->whereNotNull('codice')
            ->where('codice', '!=', '')
            ->orderBy('codice')
            ->get();
        $disco = Storage::disk(config('filesystems.files_disk'));
        $selezionato = $this->codiceId !== '' ? $codici->firstWhere('id', (int) $this->codiceId) : null;
        $scelto = $this->codiceEsistenteId !== '' ? $codici->firstWhere('id', (int) $this->codiceEsistenteId) : null;
        $elencoCodici = [];
        $codiciSenzaFoto = [];

        foreach ($codici as $codice) {
            $haFoto = $disco->exists($qr->percorso($codice->codice));
            $elencoCodici[] = [
                'id' => (string) $codice->id,
                'label' => $codice->codice.' — '.$this->etichettaCodice($codice, $haFoto),
            ];

            if ($this->eLibero($codice) && ! $haFoto) {
                $codiciSenzaFoto[] = [
                    'id' => (string) $codice->id,
                    'label' => $codice->codice,
                ];
            }
        }

        $anteprima = $this->modalita === 'esistente' ? $scelto?->codice : $prossimo;

        return view('livewire.generatore-qr', [
            'prossimo' => $prossimo,
            'anteprima' => $anteprima,
            'svg' => $anteprima ? $generatore->svg($anteprima, trim($this->titolo), $logoDataUri, $this->colore) : null,
            'elencoCodici' => collect($elencoCodici),
            'codiciSenzaFoto' => collect($codiciSenzaFoto),
            'pngSelezionato' => $selezionato && $disco->exists($qr->percorso($selezionato->codice)),
        ]);
    }

    private function salvaFotoSuCodiceEsistente(QrSede $qr, string $colore, ?string $logo, string $titolo): Codice
    {
        $codice = Codice::query()->find($this->codiceEsistenteId);

        if (! $codice instanceof Codice || $codice->codice === null || $codice->codice === '' || ! $this->eLibero($codice)) {
            throw ValidationException::withMessages([
                'codiceEsistenteId' => 'Scegli un codice libero senza foto.',
            ]);
        }

        if (Storage::disk(config('filesystems.files_disk'))->exists($qr->percorso($codice->codice))) {
            throw ValidationException::withMessages([
                'codiceEsistenteId' => 'Questo codice ha già la foto.',
            ]);
        }

        $qr->genera($codice->codice, $colore, $logo, $titolo);

        return $codice;
    }

    private function eLibero(Codice $codice): bool
    {
        return $codice->stato === Codice::CREATO
            && $codice->id_sede === null
            && $codice->id_postazione === null;
    }

    private function etichettaCodice(Codice $codice, bool $haFoto): string
    {
        $stato = $this->eLibero($codice) ? 'Libero' : 'Assegnato';

        return $stato.', '.($haFoto ? 'con foto' : 'senza foto');
    }

    /** @param  array<int, string>  $riservati */
    private function creaCodiceLibero(QrSede $qr, array $riservati, string $colore, ?string $logo, string $titolo): Codice
    {
        $stringa = null;

        for ($tentativo = 1; $tentativo <= 5; $tentativo++) {
            $proposta = Codice::prossimoCodice($riservati);

            try {
                $qr->genera($proposta, $colore, $logo, $titolo);
                $stringa = $proposta;
                break;
            } catch (RuntimeException $e) {
                if ($tentativo === 5 || ! str_contains($e->getMessage(), 'esiste già')) {
                    throw $e;
                }

                $riservati[] = $proposta;
            }
        }

        if ($stringa === null) {
            throw new RuntimeException('Impossibile generare un codice QR libero.');
        }

        try {
            return Codice::query()->create([
                'codice' => $stringa,
                'stato' => Codice::CREATO,
            ]);
        } catch (Throwable $e) {
            Storage::disk(config('filesystems.files_disk'))->delete($qr->percorso($stringa));

            throw $e;
        }
    }

    /** @param array<int, Codice> $codici */
    private function annullaCodici(array $codici, QrSede $qr): void
    {
        foreach ($codici as $codice) {
            try {
                Storage::disk(config('filesystems.files_disk'))->delete($qr->percorso($codice->codice));
                $codice->delete();
            } catch (Throwable $errorePulizia) {
                report($errorePulizia);
            }
        }
    }

    private function autorizzaStaff(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isStaff(), 403);
    }
}

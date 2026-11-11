<?php

namespace App\Http\Controllers;

use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Codice;
use App\Models\Legacy\Config;
use App\Models\Legacy\Documento;
use App\Models\Legacy\DocumentoView;
use App\Models\Legacy\Gda;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Sede;
use App\Models\Legacy\Turno;
use App\Models\User;
use App\Services\QrSede;
use App\Services\RegistroGda;
use App\Services\Timbratura;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadController extends Controller
{
    public function documento(Request $request, Documento $documento)
    {
        $user = auth()->user();
        $consentito = match (true) {
            $user->isStaff() => true,
            $user->isCliente() => $documento->tipo === 'C' && (int) $documento->id_tipo === (int) $user->cliente_id,
            default => $documento->tipo === 'O' && (int) $documento->id_tipo === (int) $user->id,
        };
        $percorso = $documento->percorso();
        abort_unless($consentito && Storage::disk(config('filesystems.files_disk'))->exists($percorso), 404);

        if ($documento->tipo === 'O' && (int) $documento->id_tipo === (int) $user->id) {
            DocumentoView::updateOrCreate(
                ['id_documento' => $documento->id, 'id_operatore' => $user->id],
                ['visto' => 'Y', 'data_vista' => now()],
            );
        }

        if ($request->boolean('visualizza')) {
            return Storage::disk(config('filesystems.files_disk'))->response($percorso);
        }

        return Storage::disk(config('filesystems.files_disk'))->download($percorso);
    }

    /** Foto della sede del turno attivo dell'operatore (getImageSede). */
    public function fotoTurnoAttivo(Timbratura $timbratura)
    {
        $percorso = $timbratura->turnoAttivo(auth()->user())?->sede?->percorsoImmagineTimbratura();
        abort_unless($percorso && Storage::disk(config('filesystems.files_disk'))->exists($percorso), 404);

        return Storage::disk(config('filesystems.files_disk'))->response($percorso, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function fotoSede(Sede $sede)
    {
        $this->autorizzaSede((int) $sede->id_cliente);
        $percorso = $sede->percorsoFoto();
        abort_unless($percorso && Storage::disk(config('filesystems.files_disk'))->exists($percorso), 404);

        return Storage::disk(config('filesystems.files_disk'))->response($percorso, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function immaginePostazione(Postazione $postazione)
    {
        $this->autorizzaSede((int) $postazione->id_cliente);
        $percorso = $postazione->percorsoFile();
        abort_unless($percorso && Storage::disk(config('filesystems.files_disk'))->exists($percorso), 404);

        return Storage::disk(config('filesystems.files_disk'))->response($percorso, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function qrSede(Sede $sede, QrSede $qr)
    {
        $this->autorizzaSede((int) $sede->id_cliente);

        return $this->scaricaQr($sede->codice, $qr);
    }

    public function qrPostazione(Postazione $postazione, QrSede $qr)
    {
        $this->autorizzaSede((int) $postazione->id_cliente);

        return $this->scaricaQr($postazione->codice, $qr);
    }

    public function qrCodice(Codice $codice, QrSede $qr)
    {
        abort_unless(auth()->user()->isStaff(), 403);

        return $this->scaricaQr($codice, $qr);
    }

    private function scaricaQr(?Codice $codice, QrSede $qr)
    {
        abort_unless($codice, 404, 'Nessun QR associato.');
        $percorso = $qr->percorso($codice->codice);
        $disco = Storage::disk(config('filesystems.files_disk'));
        abort_unless($disco->exists($percorso), 404, "L'immagine QR non è disponibile nello storage.");

        return $disco->download($percorso, basename($percorso), [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    private function autorizzaSede(int $clienteId): void
    {
        abort_unless(Cliente::visibiliA(auth()->user())->whereKey($clienteId)->exists(), 403);
    }

    public function fotoProfilo(User $user)
    {
        $corrente = auth()->user();
        abort_unless($corrente->id === $user->id || $corrente->isStaff(), 403);
        $directoryAvatar = trim(config('filesystems.private_paths.avatars'), '/');
        $percorso = $user->foto_path ? $directoryAvatar.'/'.basename($user->foto_path) : null;
        $disco = Storage::disk(config('filesystems.files_disk'));

        if (! $percorso || ! $disco->exists($percorso)) {
            $percorso = $directoryAvatar.'/default.jpg';
        }

        abort_unless($disco->exists($percorso), 404);

        return $disco->response($percorso, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    /** Registro GDA di un cliente/sede: pagina A4 orizzontale da stampare o salvare in PDF (actionStampa di Yii). */
    public function stampaGda(Request $request, RegistroGda $registro)
    {
        $user = auth()->user();
        $filtri = $request->validate([
            'cliente' => ['nullable', 'integer', 'min:1'],
            'sede' => ['nullable', 'integer', 'min:1'],
            'operatore' => ['nullable', 'integer', 'min:1'],
            'da' => ['nullable', 'date'],
            'a' => ['nullable', 'date', 'after_or_equal:da'],
        ]);
        abort_if(
            ! $user->isCliente()
                && empty($filtri['cliente'])
                && empty($filtri['sede'])
                && empty($filtri['operatore']),
            422,
            'Seleziona un cliente, una sede o un operatore.',
        );

        $visibili = Cliente::visibiliA($user);

        $sede = (int) ($filtri['sede'] ?? 0) ?: null;
        $idCliente = (int) ($filtri['cliente'] ?? 0) ?: null;
        $idCliente ??= $sede ? Sede::whereKey($sede)->value('id_cliente') : null;
        $idCliente ??= $user->isCliente() ? $user->id_cliente : null;

        $cliente = $idCliente ? (clone $visibili)->with(['comune', 'provincia'])->findOrFail($idCliente) : null;

        return view('stampe.gda', [
            'azienda' => Config::first(),
            'cliente' => $cliente,
            'turni' => $registro->turni(
                $user,
                isset($filtri['da']) ? Carbon::parse($filtri['da']) : null,
                isset($filtri['a']) ? Carbon::parse($filtri['a']) : null,
                $cliente?->id,
                $sede,
                (int) ($filtri['operatore'] ?? 0) ?: null,
            ),
        ]);
    }

    public function presenze(Request $request)
    {
        $user = auth()->user();
        $visibili = Cliente::visibiliA($user)->select('tc_clienti.id');

        $righe = Gda::with(['operatore', 'sede'])
            ->whereIn('id_cliente', $visibili)
            ->when($request->date('da'), fn ($q, $d) => $q->where('data', '>=', $d->toDateString()))
            ->when($request->date('a'), fn ($q, $d) => $q->where('data', '<=', $d->toDateString()))
            ->when($request->integer('sede'), fn ($q, $id) => $q->where('id_sede', $id))
            ->orderBy('data')->orderBy('orario')
            ->get();

        return response()->streamDownload(function () use ($righe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data', 'Ora', 'Operatore', 'Sede', 'Tipo', 'Scarto (min)', 'Distanza (m)'], ';');
            foreach ($righe as $p) {
                fputcsv($out, [
                    $p->data?->format('d/m/Y'), $p->orario,
                    $p->operatore?->nome_completo, $p->sede?->descrizione,
                    ['I' => 'Inizio', 'F' => 'Fine', 'P' => 'Passaggio'][$p->tipo] ?? $p->tipo,
                    $p->ritardo, $p->distanza !== null ? round($p->distanza * 1000) : '',
                ], ';');
            }
            fclose($out);
        }, 'presenze-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function oreTurni(Request $request, string $tipo, int $id)
    {
        $user = auth()->user();
        abort_unless($user->isStaff() && in_array($tipo, ['cliente', 'operatore'], true), 403);

        $soggetto = $tipo === 'cliente' ? Cliente::visibiliA($user)->findOrFail($id) : Admin::findOrFail($id);
        $da = $request->date('da');
        $a = $request->date('a');

        $turni = Turno::with(['cliente', 'sede', 'operatore'])
            ->whereIn('id_cliente', Cliente::visibiliA($user)->select('tc_clienti.id'))
            ->where($tipo === 'cliente' ? 'id_cliente' : 'id_operatore', $id)
            ->when($request->integer('sede'), fn ($q, $sede) => $q->where('id_sede', $sede))
            ->when($da, fn ($q, $d) => $q->where('data_inizio', '>=', $d->toDateString()))
            ->when($a, fn ($q, $d) => $q->where('data_inizio', '<=', $d->toDateString()))
            ->orderBy('data_inizio')->orderBy('ora_inizio')
            ->get();

        $nome = $tipo === 'cliente' ? $soggetto->ragione_sociale : $soggetto->nome_completo;
        $titolo = 'ORE '.$nome.($da ? ' Dal '.$da->format('d-m-Y') : '').($a ? ' Al '.$a->format('d-m-Y') : '');

        $foglio = new Spreadsheet;
        $sheet = $foglio->getActiveSheet();
        $sheet->setTitle('Ore');
        $sheet->mergeCells('A1:I1')->setCellValue('A1', $titolo);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->fromArray(['DATA', 'ORA INIZIO', 'DATA FINE', 'ORA FINE', $tipo === 'cliente' ? 'OPERATORE' : 'CLIENTE', 'SEDE', 'ORE PREVISTE', 'ORE LAVORATE', 'SALDO'], null, 'A2');
        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
        ]);

        $riga = 3;
        $previste = $lavorate = 0.0;
        foreach ($turni as $t) {
            $saldo = (float) $t->ore_lavorate - (float) $t->ore;
            $previste += (float) $t->ore;
            $lavorate += (float) $t->ore_lavorate;
            $sheet->fromArray([
                $t->data_inizio?->format('d-m-Y'), $t->ora_inizio,
                $t->data_fine && ! $t->data_fine->equalTo($t->data_inizio) ? $t->data_fine->format('d-m-Y') : '',
                $t->ora_fine,
                $tipo === 'cliente' ? $t->operatore?->nome_completo : $t->cliente?->ragione_sociale,
                $t->sede?->descrizione, (float) $t->ore, (float) $t->ore_lavorate, $saldo != 0 ? $saldo : '',
            ], null, 'A'.$riga);
            $riga++;
        }

        $sheet->mergeCells('A'.$riga.':F'.$riga)->setCellValue('A'.$riga, 'TOTALE');
        $sheet->fromArray([$previste, $lavorate, $lavorate - $previste], null, 'G'.$riga);
        $sheet->getStyle('A'.$riga.':I'.$riga)->getFont()->setBold(true);
        foreach (range('A', 'I') as $colonna) {
            $sheet->getColumnDimension($colonna)->setAutoSize(true);
        }

        $file = preg_replace('/[^A-Za-z0-9_-]+/', '_', $titolo).'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($foglio))->save('php://output'),
            $file,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}

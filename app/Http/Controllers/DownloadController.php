<?php

namespace App\Http\Controllers;

use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use App\Models\Legacy\Config;
use App\Models\Legacy\Documento;
use App\Models\Legacy\DocumentoView;
use App\Models\Legacy\Gda;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Sede;
use App\Models\Legacy\Turno;
use App\Models\User;
use App\Services\RegistroGda;
use Illuminate\Http\Request;
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

    private function autorizzaSede(int $clienteId): void
    {
        abort_unless(Cliente::visibiliA(auth()->user())->whereKey($clienteId)->exists(), 403);
    }

    public function fotoProfilo(User $user)
    {
        $corrente = auth()->user();
        abort_unless($corrente->id === $user->id || $corrente->isStaff(), 403);
        $percorso = trim(config('filesystems.private_paths.avatars'), '/').'/'.basename((string) $user->foto_path);
        abort_unless($user->foto_path && Storage::disk(config('filesystems.files_disk'))->exists($percorso), 404);

        return Storage::disk(config('filesystems.files_disk'))->response($percorso, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    /** Registro GDA di un cliente/sede: pagina A4 orizzontale da stampare o salvare in PDF (actionStampa di Yii). */
    public function stampaGda(Request $request, RegistroGda $registro)
    {
        $user = auth()->user();
        $visibili = Cliente::visibiliA($user);

        $sede = $request->integer('sede') ?: null;
        $idCliente = $request->integer('cliente') ?: null;
        $idCliente ??= $sede ? Sede::whereKey($sede)->value('id_cliente') : null;
        $idCliente ??= $user->isCliente() ? $user->id_cliente : null;

        abort_unless($idCliente, 422, 'Seleziona un cliente o una sede.');

        $cliente = (clone $visibili)->with(['comune', 'provincia'])->findOrFail($idCliente);

        return view('stampe.gda', [
            'azienda' => Config::first(),
            'cliente' => $cliente,
            'turni' => $registro->turni($user, $request->date('da'), $request->date('a'), $cliente->id, $sede, $request->integer('operatore') ?: null),
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

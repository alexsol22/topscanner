<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GDA {{ $cliente->ragione_sociale }}</title>
    <style>
        @page { size: A4 landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 16px; }
        table { width: 100%; border-collapse: collapse; }
        .intestazione td { vertical-align: top; }
        .destra { text-align: right; }
        .grande { font-size: 16px; font-weight: bold; margin: 0 0 4px; }
        .medio { margin: 2px 0; }
        .titolo { margin: 14px 0 6px; font-size: 14px; font-weight: bold; }
        .registro th { background: #e6e6e6; text-align: left; padding: 5px; border: 1px solid #bbb; }
        .registro td { padding: 4px 5px; border: 1px solid #ddd; }
        .registro tr { break-inside: avoid; }
        .registro thead { display: table-header-group; }
        .turno td { background: #ccc; font-weight: bold; }
        .principale td { background: #f4f4f4; }
        .piede { margin-top: 12px; color: #555; }
        .azioni { margin-bottom: 12px; }
        @media print { .azioni { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="azioni"><button type="button" onclick="window.print()">Stampa / Salva PDF</button></div>

    <table class="intestazione">
        <tr>
            <td style="width: 150px"><img src="{{ asset('brand/logo-security-scanner.png') }}" alt="" style="width: 120px"></td>
            <td>
                <p class="grande">{{ $azienda?->app }}</p>
                <p class="medio">{{ $azienda?->societa_indirizzo }}</p>
                <p class="medio">{{ $azienda?->societa_cap }} {{ $azienda?->societa_comune }} ({{ $azienda?->societa_provincia }})</p>
                <p class="medio">P.Iva: {{ $azienda?->societa_iva }}</p>
            </td>
            <td class="destra">
                <p class="grande">{{ $cliente->ragione_sociale }}</p>
                <p class="medio">{{ $cliente->indirizzo }}</p>
                <p class="medio">{{ $cliente->cap }} {{ $cliente->citta }} ({{ $cliente->provincia_sigla }})</p>
                <p class="medio">P.Iva: {{ $cliente->piva }}</p>
                <p class="medio">Tel: {{ $cliente->telefono }}</p>
            </td>
        </tr>
    </table>

    <div class="titolo">Giornale delle attività (GDA)</div>

    <table class="registro">
        <thead>
            <tr>
                <th style="width: 20%">Sede / Postazione</th>
                <th style="width: 15%">Operatore</th>
                <th style="width: 10%">Inizio Turno</th>
                <th style="width: 10%">Fine Turno</th>
                <th style="width: 5%">Ore</th>
                <th style="width: 10%">Passaggio</th>
                <th style="width: 30%">Segnalazioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($turni as $turno)
                <tr class="turno">
                    <td>{{ $turno['sede'] }} {{ $turno['data'] }}</td>
                    <td colspan="6">{{ $turno['inizio']->operatore?->cognome }} {{ $turno['inizio']->operatore?->nome }}</td>
                </tr>
                <tr class="principale">
                    <td>Postazione principale</td>
                    <td></td>
                    <td>{{ $turno['inizio']->orario }}</td>
                    <td>{{ $turno['fine']?->orario }}</td>
                    <td>{{ $turno['lavorate'] }}</td>
                    <td></td>
                    <td>{{ trim($turno['inizio']->note_operatore.' '.$turno['fine']?->note_operatore) }}</td>
                </tr>
                @foreach ($turno['giri'] as $giro)
                    @foreach ($giro as $indice => $passaggio)
                        <tr>
                            <td>{{ $indice === 0 ? $passaggio->postazione?->titolo : '' }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $passaggio->orario }}</td>
                            <td>{{ $passaggio->note_operatore }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @empty
                <tr><td colspan="7">Nessuna timbratura nel periodo selezionato.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="piede">Email: {{ $azienda?->societa_email }} &nbsp;·&nbsp; Tel: {{ $azienda?->societa_telefono }}</div>
</body>
</html>

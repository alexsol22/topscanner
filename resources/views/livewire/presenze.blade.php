@if (in_array($vista, ['clienti', 'operatori'], true))
    <div class="space-y-5">
        <h2 class="text-lg font-semibold text-slate-800">
            {{ $vista === 'clienti' ? 'Conteggio orario clienti' : 'Conteggio orario operatori' }}
            Dal {{ \Carbon\Carbon::parse($da)->format('d-m-Y') }} al {{ \Carbon\Carbon::parse($a)->format('d-m-Y') }}
        </h2>

        <x-ui.filtri action="applicaFiltri" :values="array_merge(
            ['da' => $da, 'a' => $a],
            $vista === 'clienti' ? ['filtroCliente' => $filtroCliente, 'filtroSede' => $filtroSede] : ['filtroOperatore' => $filtroOperatore],
        )">
        <div class="grid gap-3 sm:grid-cols-2" wire:key="presenze-filtri-{{ $vista }}">
            @if ($vista === 'clienti')
                <div><label class="label">Cliente</label><x-select-ricerca model="filtroCliente" :draft="true" :options="$clientiElenco->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()" /></div>
                <div><label class="label">Sede</label><x-select-ricerca model="filtroSede" :draft="true" :options="$sedi->map(fn ($s) => ['id' => $s->id, 'label' => $s->descrizione])->values()" /></div>
            @else
                <div><label class="label">Operatore</label><x-select-ricerca model="filtroOperatore" :draft="true" :options="$operatori->map(fn ($o) => ['id' => $o->id, 'label' => $o->nome_completo])->values()" /></div>
            @endif
            <div><label class="label">Dal</label><input type="date" x-model="valori.da" :max="valori.a || null" class="input"></div>
            <div><label class="label">Al</label><input type="date" x-model="valori.a" :min="valori.da || null" class="input"></div>
        </div>
        </x-ui.filtri>

        @if ($vista === 'clienti')
            <div class="card overflow-hidden">
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
                    <thead class="bg-sky-50/60"><tr><th class="th">Cliente</th><th class="th">Ore previste</th><th class="th">Ore lavorate</th><th class="th">Saldo</th><th class="th">Scarica</th></tr></thead>
                    <tbody class="divide-y divide-sky-50">
                        @foreach ($perCliente as $idCliente => $cliente)
                            @php($saldo = $cliente['lavorate'] - $cliente['previste'])
                            <tr>
                                <td class="td font-medium">{{ $cliente['nome'] }}</td>
                                <td class="td">{{ $cliente['previste'] }}</td>
                                <td class="td">{{ $cliente['lavorate'] }}</td>
                                <td class="td {{ $saldo < 0 ? 'text-rose-600' : ($saldo > 0 ? 'text-emerald-600' : '') }}">{{ $saldo }}</td>
                                <td class="td"><a href="{{ route('presenze.ore', ['tipo' => 'cliente', 'id' => $idCliente, 'da' => $da, 'a' => $a, 'sede' => $filtroSede]) }}" class="btn-soft">Scarica</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
                    <thead class="bg-sky-50/60"><tr><th class="th">Codice</th><th class="th">Operatore</th><th class="th">Ore previste</th><th class="th">Ore lavorate</th><th class="th">Saldo</th><th class="th">Scarica</th></tr></thead>
                    <tbody class="divide-y divide-sky-50">
                        @foreach ($perOperatore as $idOperatore => $operatore)
                            <tr>
                                <td class="td">{{ $operatore['codice'] }}</td>
                                <td class="td font-medium">{{ $operatore['nome'] }}</td>
                                <td class="td">{{ $operatore['previste'] }}</td>
                                <td class="td">{{ $operatore['lavorate'] }}</td>
                                <td class="td {{ $operatore['lavorate'] - $operatore['previste'] < 0 ? 'text-rose-600' : '' }}">{{ $operatore['lavorate'] - $operatore['previste'] }}</td>
                                <td class="td"><a href="{{ route('presenze.ore', ['tipo' => 'operatore', 'id' => $idOperatore, 'da' => $da, 'a' => $a]) }}" class="btn-soft">Scarica</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif
    </div>
@else
<div class="space-y-5">
    <h2 class="text-xl font-semibold text-slate-800">Giornale delle attività</h2>

    <x-ui.filtri action="applicaFiltri" :values="array_merge(
        ['filtroSede' => $filtroSede, 'da' => $da, 'a' => $a],
        auth()->user()->isStaff() ? ['filtroCliente' => $filtroCliente, 'filtroOperatore' => $filtroOperatore] : [],
    )">
    <div class="grid gap-3 sm:grid-cols-2" wire:key="presenze-filtri-{{ $vista }}">
        @if (auth()->user()->isStaff())
            <div><label class="label">Cliente</label><x-select-ricerca model="filtroCliente" :draft="true" :options="$clientiElenco->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()" /></div>
        @endif
        <div><label class="label">Sede</label><x-select-ricerca model="filtroSede" :draft="true" :options="$sedi->map(fn ($s) => ['id' => $s->id, 'label' => $s->descrizione])->values()" /></div>
        @if (auth()->user()->isStaff())
            <div><label class="label">Operatore</label><x-select-ricerca model="filtroOperatore" :draft="true" :options="$operatori->map(fn ($o) => ['id' => $o->id, 'label' => $o->nome_completo])->values()" /></div>
        @endif
        <div><label class="label">Dal</label><input type="datetime-local" x-model="valori.da" :max="valori.a || null" class="input"></div>
        <div><label class="label">Al</label><input type="datetime-local" x-model="valori.a" :min="valori.da || null" class="input"></div>
    </div>
    </x-ui.filtri>

    @if ($totali)
        <p class="text-sm text-slate-600">
            Totale: ore previste <b>{{ $totali['previste'] }}</b>
            · Totale: ore lavorate <b>{{ $totali['lavorate'] }}</b>
            · Totale: saldo <b class="{{ $totali['saldo'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $totali['saldo'] }}</b>
        </p>
    @endif

    <div class="flex justify-end">
        @if (auth()->user()->isCliente() || $filtroCliente || $filtroSede || $filtroOperatore)
            <a href="{{ route('presenze.stampa', ['da' => $da, 'a' => $a, 'sede' => $filtroSede, 'cliente' => $filtroCliente, 'operatore' => $filtroOperatore]) }}" target="_blank" rel="noopener" class="btn-soft">Stampa</a>
        @else
            <button type="button" disabled title="Seleziona un cliente, una sede o un operatore" class="btn-soft cursor-not-allowed opacity-50">Stampa</button>
        @endif
    </div>

    @php($staff = auth()->user()->isStaff())
    <div class="card overflow-hidden">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
            <thead class="bg-sky-50/60"><tr>
                @if ($staff)<th class="th">Ragione Sociale</th>@endif
                <th class="th">Sede</th><th class="th">Operattore</th><th class="th">Inizio Turno</th><th class="th">Fine Turno</th>
                <th class="th">Ore Previste</th><th class="th">Ore Lavorate</th><th class="th">Note Operatore</th><th class="th">Passaggi</th>
                @if ($staff)<th class="th">Distanza</th>@endif
            </tr></thead>
            <tbody class="divide-y divide-sky-50">
                @forelse ($turniGda as $t)
                    @php($inizioGda = $t->presenze->firstWhere('tipo', 'I'))
                    @php($fineGda = $t->presenze->firstWhere('tipo', 'F'))
                    @php($passaggi = $t->presenze->where('tipo', 'P'))
                    <tr wire:key="gda{{ $t->id }}">
                        @if ($staff)<td class="td">{{ $t->cliente?->ragione_sociale }}</td>@endif
                        <td class="td">{{ $t->sede?->descrizione }}</td>
                        <td class="td">{{ $t->operatore?->codice }} ({{ trim(($t->operatore?->cognome ?? '').' '.($t->operatore?->nome ?? '')) }})</td>
                        <td class="td whitespace-nowrap">@if ($inizioGda){{ $inizioGda->data?->format('d-m-Y') }}<br>Ore: {{ $inizioGda->orario }}@endif</td>
                        <td class="td whitespace-nowrap">@if ($fineGda){{ $fineGda->data?->format('d-m-Y') }}<br>Ore: {{ $fineGda->orario }}@endif</td>
                        <td class="td">{{ $t->ore }}</td>
                        <td class="td font-semibold {{ $staff && $t->ore > $t->ore_lavorate ? 'text-rose-600' : ($staff && $t->ore < $t->ore_lavorate ? 'text-emerald-600' : '') }}">{{ $t->ore_lavorate }}</td>
                        <td class="td text-sm">
                            @foreach ($t->presenze->whereIn('tipo', ['I', 'F']) as $presenza)
                                @if ($presenza->note_operatore)
                                    <p><b>{{ $presenza->tipo === 'I' ? 'Inizio turno:' : 'Fine turno:' }}</b> {{ $presenza->note_operatore }}</p>
                                @endif
                            @endforeach
                        </td>
                        <td class="td">@if ($passaggi->isNotEmpty())<button type="button" wire:click="mostraPassaggi({{ $t->id }})" class="btn-soft !px-3 !py-1.5" title="Visualizza passaggi">Passaggi ({{ $passaggi->count() }})</button>@endif</td>
                        @if ($staff)<td class="td whitespace-nowrap">{{ $inizioGda && $inizioGda->distanza > 0 ? $inizioGda->distanza.' Km' : '' }}</td>@endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $staff ? 10 : 8 }}" class="td py-10 text-center text-slate-500">Non sono stati trovati presenze che soddisfino i parametri di ricerca inseriti</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    {{ $turniGda->links() }}

    @if ($turnoPassaggi)
        <x-ui.modal title="Passaggi postazioni — {{ $turnoPassaggi->sede?->descrizione }}" close="chiudiPassaggi">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
                <thead class="bg-sky-50/60"><tr><th class="th">Postazione</th><th class="th">Orario</th><th class="th">Note operatore</th>@if ($staff)<th class="th">Ritardo</th>@endif</tr></thead>
                <tbody class="divide-y divide-sky-50">
                    @foreach ($turnoPassaggi->presenze->where('tipo', 'P') as $p)
                        <tr>
                            <td class="td">{{ $p->postazione?->titolo }}</td>
                            <td class="td whitespace-nowrap">{{ $p->data?->format('d-m-Y') }}<br>Ore {{ $p->orario }}</td>
                            <td class="td">{{ $p->note_operatore }}</td>
                            @if ($staff)<td class="td">{{ $p->ritardo > 0 ? $p->ritardo.' Min' : '' }}</td>@endif
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </x-ui.modal>
    @endif
</div>
@endif

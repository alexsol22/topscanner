<div class="space-y-5">
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        <div><label class="label">Dal</label><input type="date" wire:model.live="da" class="input"></div>
        <div><label class="label">Al</label><input type="date" wire:model.live="a" class="input"></div>
        <div><label class="label">Sede</label><x-select-ricerca model="filtroSede" :options="$sedi->map(fn ($s) => ['id' => $s->id, 'label' => $s->descrizione])->values()" /></div>
        @unless (auth()->user()->isCliente())
            @if ($vista === 'clienti')
                <div wire:key="filtro-cliente"><label class="label">Cliente</label><x-select-ricerca model="filtroCliente" :options="$clientiElenco->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()" /></div>
            @else
                <div wire:key="filtro-operatore"><label class="label">Operatore</label><x-select-ricerca model="filtroOperatore" :options="$operatori->map(fn ($o) => ['id' => $o->id, 'label' => $o->nome_completo])->values()" /></div>
            @endif
        @endunless
    </div>

    @if ($totali)
        <div class="grid grid-cols-3 gap-4">
            <div class="card p-5"><p class="label">Totale ore previste ({{ $totali['etichetta'] }})</p><p class="text-2xl font-bold text-sky-600">{{ $totali['previste'] }}</p></div>
            <div class="card p-5"><p class="label">Totale ore lavorate</p><p class="text-2xl font-bold text-emerald-600">{{ $totali['lavorate'] }}</p></div>
            <div class="card p-5"><p class="label">Totale saldo</p><p class="text-2xl font-bold {{ $totali['saldo'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $totali['saldo'] }}</p></div>
        </div>
    @endif

    <div class="flex justify-end">
        @if ($filtroCliente || $filtroSede || auth()->user()->isCliente())
            <a href="{{ route('presenze.stampa', ['da' => $da, 'a' => $a, 'sede' => $filtroSede, 'cliente' => $filtroCliente]) }}" target="_blank" rel="noopener" class="btn-soft mr-2">🖨 Stampa GDA</a>
        @endif
        <a href="{{ route('presenze.esporta', ['da' => $da, 'a' => $a, 'sede' => $filtroSede]) }}" class="btn-soft">⬇ Esporta CSV</a>
    </div>

    @unless (auth()->user()->isCliente())
        <div class="flex gap-2" role="tablist" aria-label="Conteggio ore per">
            @foreach (['clienti' => 'Clienti', 'operatori' => 'Operatori'] as $chiave => $etichetta)
                <button type="button" role="tab" aria-selected="{{ $vista === $chiave ? 'true' : 'false' }}" wire:click="$set('vista', '{{ $chiave }}')"
                    class="{{ $vista === $chiave ? 'btn-primary' : 'btn-soft' }}">{{ $etichetta }}</button>
            @endforeach
        </div>
    @endunless

    @if ($vista === 'clienti' && $perCliente->isNotEmpty() && ! auth()->user()->isCliente())
        <div class="card overflow-hidden">
            <div class="border-b border-sky-100 px-5 py-4 font-semibold text-slate-800">Conteggio orario clienti</div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
                <thead class="bg-sky-50/60"><tr><th class="th">Cliente</th><th class="th">Ore previste</th><th class="th">Ore lavorate</th><th class="th">Saldo</th><th class="th"></th></tr></thead>
                <tbody class="divide-y divide-sky-50">
                    @foreach ($perCliente as $idCliente => $c)
                        @php($saldo = $c['lavorate'] - $c['previste'])
                        <tr><td class="td font-medium">{{ $c['nome'] }}</td><td class="td">{{ $c['previste'] }}</td><td class="td">{{ $c['lavorate'] }}</td><td class="td font-semibold {{ $saldo < 0 ? 'text-rose-600' : ($saldo > 0 ? 'text-emerald-600' : '') }}">{{ $saldo }}</td><td class="td whitespace-nowrap text-right"><a href="{{ route('presenze.ore', ['tipo' => 'cliente', 'id' => $idCliente, 'da' => $da, 'a' => $a, 'sede' => $filtroSede]) }}" class="btn-soft shrink-0 whitespace-nowrap !px-3 !py-1.5" title="Scarica Excel" aria-label="Scarica Excel di {{ $c['nome'] }}">⬇ Excel</a></td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    @endif

    @if ($vista === 'operatori' && $perOperatore->isNotEmpty() && ! auth()->user()->isCliente())
        <div class="card overflow-hidden">
            <div class="border-b border-sky-100 px-5 py-4 font-semibold text-slate-800">Ore per operatore</div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
                <thead class="bg-sky-50/60"><tr><th class="th">Operatore</th><th class="th">Previste</th><th class="th">Lavorate</th><th class="th">Saldo</th><th class="th"></th></tr></thead>
                <tbody class="divide-y divide-sky-50">
                    @foreach ($perOperatore as $idOperatore => $o)
                        <tr><td class="td font-medium">{{ $o['nome'] }}</td><td class="td">{{ $o['previste'] }}</td><td class="td">{{ $o['lavorate'] }}</td><td class="td {{ $o['lavorate'] - $o['previste'] < 0 ? 'text-rose-600' : '' }}">{{ $o['lavorate'] - $o['previste'] }}</td><td class="td whitespace-nowrap text-right"><a href="{{ route('presenze.ore', ['tipo' => 'operatore', 'id' => $idOperatore, 'da' => $da, 'a' => $a, 'sede' => $filtroSede]) }}" class="btn-soft shrink-0 whitespace-nowrap !px-3 !py-1.5" title="Scarica Excel" aria-label="Scarica Excel di {{ $o['nome'] }}">⬇ Excel</a></td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    @endif

    @php($staff = auth()->user()->isStaff())
    <div class="card overflow-hidden">
        <div class="border-b border-sky-100 px-5 py-4 font-semibold text-slate-800">Giornale delle attività (turni completati)</div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
            <thead class="bg-sky-50/60"><tr>
                @unless (auth()->user()->isCliente())<th class="th">Cliente</th>@endunless
                <th class="th">Sede</th><th class="th">Operatore</th><th class="th">Inizio turno</th><th class="th">Fine turno</th>
                <th class="th">Ore previste</th><th class="th">Ore lavorate</th><th class="th">Note operatore</th><th class="th">Passaggi</th>
                @if ($staff)<th class="th">Distanza</th><th class="th">Ritardo</th>@endif
            </tr></thead>
            <tbody class="divide-y divide-sky-50">
                @forelse ($turniGda as $t)
                    @php($inizioGda = $t->presenze->firstWhere('tipo', 'I'))
                    @php($fineGda = $t->presenze->firstWhere('tipo', 'F'))
                    @php($passaggi = $t->presenze->where('tipo', 'P'))
                    <tr wire:key="gda{{ $t->id }}">
                        @unless (auth()->user()->isCliente())<td class="td">{{ $t->cliente?->ragione_sociale }}</td>@endunless
                        <td class="td">{{ $t->sede?->descrizione }}</td>
                        <td class="td">{{ $t->operatore?->nome_completo }}</td>
                        <td class="td whitespace-nowrap">@if ($inizioGda){{ $inizioGda->data?->format('d-m-Y') }}<br>Ore: {{ $inizioGda->orario }}@endif</td>
                        <td class="td whitespace-nowrap">@if ($fineGda){{ $fineGda->data?->format('d-m-Y') }}<br>Ore: {{ $fineGda->orario }}@endif</td>
                        <td class="td">{{ $t->ore }}</td>
                        <td class="td font-semibold {{ $staff && $t->ore > $t->ore_lavorate ? 'text-rose-600' : ($staff && $t->ore < $t->ore_lavorate ? 'text-emerald-600' : '') }}">{{ $t->ore_lavorate }}</td>
                        <td class="td text-sm">
                            @if ($inizioGda && $inizioGda->note_operatore)<p><b>Inizio turno:</b> {{ $inizioGda->note_operatore }}</p>@endif
                            @if ($fineGda && $fineGda->note_operatore)<p><b>Fine turno:</b> {{ $fineGda->note_operatore }}</p>@endif
                        </td>
                        <td class="td">@if ($passaggi->isNotEmpty())<button type="button" wire:click="mostraPassaggi({{ $t->id }})" class="btn-soft !px-3 !py-1.5" title="Visualizza passaggi">Passaggi ({{ $passaggi->count() }})</button>@endif</td>
                        @if ($staff)
                            <td class="td whitespace-nowrap">{{ $inizioGda && $inizioGda->distanza > 0 ? $inizioGda->distanza.' Km' : '' }}</td>
                            <td class="td whitespace-nowrap">{{ $inizioGda && $inizioGda->ritardo > 0 ? $inizioGda->ritardo.' Min' : '' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="11" class="td py-10 text-center text-slate-500">Nessun turno completato nel periodo.</td></tr>
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

    <div class="card overflow-hidden">
        <div class="border-b border-sky-100 px-5 py-4 font-semibold text-slate-800">Registro timbrature</div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
            <thead class="bg-sky-50/60"><tr><th class="th">Data e ora</th><th class="th">Operatore</th><th class="th">Sede</th><th class="th">Evento</th><th class="th">Scarto</th><th class="th">Distanza</th></tr></thead>
            <tbody class="divide-y divide-sky-50">
                @forelse ($righe as $p)
                    <tr wire:key="pr{{ $p->id }}">
                        <td class="td whitespace-nowrap">{{ $p->rilevata_at->format('d/m/Y H:i') }}</td>
                        <td class="td">{{ $p->operatore?->nome_completo }}</td>
                        <td class="td">{{ $p->sede?->descrizione }}</td>
                        <td class="td">{{ ['I' => 'Inizio', 'F' => 'Fine', 'P' => 'Passaggio'][$p->tipo] }}{{ $p->postazione ? ' · '.$p->postazione->titolo : '' }}</td>
                        <td class="td">@if ($p->ritardo_minuti !== null)<span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $p->ritardo_minuti > 5 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $p->ritardo_minuti > 0 ? '+' : '' }}{{ $p->ritardo_minuti }} min</span>@endif</td>
                        <td class="td">{{ $p->distanza !== null ? round($p->distanza * 1000).' m' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td py-10 text-center text-slate-500">Nessuna timbratura nel periodo.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    {{ $righe->links() }}
</div>

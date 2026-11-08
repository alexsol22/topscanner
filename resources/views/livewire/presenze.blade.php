@php
    $staff = auth()->user()->isStaff();
@endphp

@if ($vista === 'gda')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 rounded-3xl border border-sky-100 bg-gradient-to-br from-white to-sky-50/80 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="space-y-1">
                <p class="text-sm font-semibold uppercase tracking-wide text-sky-700">Presenze</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Giornale delle attività</h1>
                <p class="text-sm text-slate-600">Consulta turni completati, passaggi e ore lavorate.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.filtri title="Filtri giornale attività" trigger-label="Apri filtri" action="applicaFiltri" :values="array_merge(
                    ['filtroSede' => $filtroSede, 'da' => $da, 'a' => $a],
                    $staff ? ['filtroCliente' => $filtroCliente, 'filtroOperatore' => $filtroOperatore] : [],
                )">
                    <div class="grid gap-4 sm:grid-cols-2" wire:key="presenze-filtri-{{ $vista }}">
                        @if ($staff)
                            <div>
                                <label class="label">Cliente</label>
                                <x-select-ricerca model="filtroCliente" :draft="true" :options="$clientiElenco->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()" />
                            </div>
                        @endif
                        <div>
                            <label class="label">Sede</label>
                            <x-select-ricerca model="filtroSede" :draft="true" dipende-da="filtroCliente" :options="$sedi->map(fn ($s) => ['id' => $s->id, 'label' => $s->descrizione, 'padre' => $s->id_cliente])->values()" />
                        </div>
                        @if ($staff)
                            <div>
                                <label class="label">Operatore</label>
                                <x-select-ricerca model="filtroOperatore" :draft="true" :options="$operatori->map(fn ($o) => ['id' => $o->id, 'label' => $o->nome_completo])->values()" />
                            </div>
                        @endif
                        <div>
                            <label class="label">Dal</label>
                            <input type="date" x-model="valori.da" :max="valori.a || null" class="input">
                        </div>
                        <div>
                            <label class="label">Al</label>
                            <input type="date" x-model="valori.a" :min="valori.da || null" class="input">
                        </div>
                    </div>
                </x-ui.filtri>

                @if (auth()->user()->isCliente() || $filtroCliente || $filtroSede || $filtroOperatore)
                    <a href="{{ route('presenze.stampa', ['da' => $da, 'a' => $a, 'sede' => $filtroSede, 'cliente' => $filtroCliente, 'operatore' => $filtroOperatore]) }}" target="_blank" rel="noopener" class="btn-primary">Stampa</a>
                @else
                    <button type="button" disabled title="Seleziona un cliente, una sede o un operatore" class="btn-soft cursor-not-allowed opacity-50">Stampa</button>
                @endif
            </div>
        </section>

        <div class="space-y-6">
            @if ($totali)
                <section aria-label="Riepilogo ore" class="grid gap-3 sm:grid-cols-3">
                    <article class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm">
                        <p class="text-sm text-slate-500">Ore previste</p>
                        <p class="mt-1 text-2xl font-bold text-slate-800">{{ $totali['previste'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm">
                        <p class="text-sm text-slate-500">Ore lavorate</p>
                        <p class="mt-1 text-2xl font-bold text-sky-700">{{ $totali['lavorate'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm">
                        <p class="text-sm text-slate-500">Saldo</p>
                        <p @class([
                            'mt-1 text-2xl font-bold',
                            'text-emerald-600' => $totali['saldo'] > 0,
                            'text-rose-600' => $totali['saldo'] < 0,
                            'text-slate-800' => $totali['saldo'] === 0.0,
                        ])>{{ $totali['saldo'] }}</p>
                    </article>
                </section>
            @endif

            <section class="overflow-hidden rounded-3xl border border-sky-100 bg-white shadow-sm">
                <div class="flex flex-col gap-1 border-b border-sky-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-semibold text-slate-800">Turni completati</h2>
                    <p class="text-sm text-slate-500">{{ $turniGda->total() }} risultati</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sky-50">
                        <thead class="bg-sky-50/70">
                            <tr>
                                @if ($staff)<th class="th">Ragione Sociale</th>@endif
                                <th class="th">Sede</th>
                                <th class="th">Operatore</th>
                                <th class="th">Inizio Turno</th>
                                <th class="th">Fine Turno</th>
                                <th class="th">Ore Previste</th>
                                <th class="th">Ore Lavorate</th>
                                <th class="th">Note Operatore</th>
                                <th class="th">Passaggi</th>
                                @if ($staff)<th class="th">Distanza</th>@endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50">
                            @forelse ($turniGda as $t)
                                @php
                                    $inizioGda = $t->presenze->firstWhere('tipo', 'I');
                                    $fineGda = $t->presenze->firstWhere('tipo', 'F');
                                    $passaggi = $t->presenze->where('tipo', 'P');
                                @endphp
                                <tr wire:key="gda{{ $t->id }}" class="transition hover:bg-sky-50/40">
                                    @if ($staff)<td class="td font-medium">{{ $t->cliente?->ragione_sociale }}</td>@endif
                                    <td class="td">{{ $t->sede?->descrizione }}</td>
                                    <td class="td">{{ $t->operatore?->codice }} ({{ trim(($t->operatore?->cognome ?? '').' '.($t->operatore?->nome ?? '')) }})</td>
                                    <td class="td whitespace-nowrap">@if ($inizioGda){{ $inizioGda->data?->format('d-m-Y') }}<br>Ore: {{ $inizioGda->orario }}@endif</td>
                                    <td class="td whitespace-nowrap">@if ($fineGda){{ $fineGda->data?->format('d-m-Y') }}<br>Ore: {{ $fineGda->orario }}@endif</td>
                                    <td class="td">{{ $t->ore }}</td>
                                    <td @class(['td font-semibold', 'text-rose-600' => $staff && $t->ore > $t->ore_lavorate, 'text-emerald-600' => $staff && $t->ore < $t->ore_lavorate])>{{ $t->ore_lavorate }}</td>
                                    <td class="td min-w-48 text-sm">
                                        @foreach ($t->presenze->whereIn('tipo', ['I', 'F']) as $presenza)
                                            @if ($presenza->note_operatore)
                                                <p><b>{{ $presenza->tipo === 'I' ? 'Inizio turno:' : 'Fine turno:' }}</b> {{ $presenza->note_operatore }}</p>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="td">
                                        @if ($passaggi->isNotEmpty())
                                            <button type="button" wire:click="mostraPassaggi({{ $t->id }})" class="btn-soft !px-3 !py-1.5" title="Visualizza passaggi">Passaggi ({{ $passaggi->count() }})</button>
                                        @endif
                                    </td>
                                    @if ($staff)<td class="td whitespace-nowrap">{{ $inizioGda && $inizioGda->distanza > 0 ? $inizioGda->distanza.' Km' : '' }}</td>@endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $staff ? 10 : 8 }}" class="px-6 py-14 text-center">
                                    <p class="font-semibold text-slate-700">Nessun turno trovato</p>
                                    <p class="mt-1 text-sm text-slate-500">Modifica i filtri per visualizzare altre presenze.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($turniGda->hasPages())
                    <div class="border-t border-sky-100 px-4 py-3">{{ $turniGda->links() }}</div>
                @endif
            </section>
        </div>

        @if ($turnoPassaggi)
            <x-ui.modal title="Passaggi postazioni — {{ $turnoPassaggi->sede?->descrizione }}" close="chiudiPassaggi">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sky-50">
                        <thead class="bg-sky-50/60">
                            <tr><th class="th">Postazione</th><th class="th">Orario</th><th class="th">Note operatore</th>@if ($staff)<th class="th">Ritardo</th>@endif</tr>
                        </thead>
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
                    </table>
                </div>
            </x-ui.modal>
        @endif
    </div>
@else
    @php
        $titoloRiepilogo = $vista === 'clienti' ? 'Conteggio orario clienti' : 'Conteggio orario operatori';
        $valuesFiltri = array_merge(
            ['da' => $da, 'a' => $a],
            $vista === 'clienti' ? ['filtroCliente' => $filtroCliente, 'filtroSede' => $filtroSede] : ['filtroOperatore' => $filtroOperatore],
        );
    @endphp
    <div class="space-y-6">
        <section class="flex flex-col gap-4 rounded-3xl border border-sky-100 bg-gradient-to-br from-white to-sky-50/80 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="space-y-1">
                <p class="text-sm font-semibold uppercase tracking-wide text-sky-700">Riepilogo presenze</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $titoloRiepilogo }}</h1>
                <p class="text-sm text-slate-600">Periodo: {{ \Carbon\Carbon::parse($da)->format('d-m-Y') }} – {{ \Carbon\Carbon::parse($a)->format('d-m-Y') }}</p>
            </div>
            <x-ui.filtri title="Filtri riepilogo ore" trigger-label="Apri filtri" action="applicaFiltri" :values="$valuesFiltri">
                <div class="grid gap-4 sm:grid-cols-2" wire:key="presenze-filtri-{{ $vista }}">
                    @if ($vista === 'clienti')
                        <div>
                            <label class="label">Cliente</label>
                            <x-select-ricerca model="filtroCliente" :draft="true" :options="$clientiElenco->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()" />
                        </div>
                        <div>
                            <label class="label">Sede</label>
                            <x-select-ricerca model="filtroSede" :draft="true" dipende-da="filtroCliente" :options="$sedi->map(fn ($s) => ['id' => $s->id, 'label' => $s->descrizione, 'padre' => $s->id_cliente])->values()" />
                        </div>
                    @else
                        <div>
                            <label class="label">Operatore</label>
                            <x-select-ricerca model="filtroOperatore" :draft="true" :options="$operatori->map(fn ($o) => ['id' => $o->id, 'label' => $o->nome_completo])->values()" />
                        </div>
                    @endif
                    <div>
                        <label class="label">Dal</label>
                        <input type="date" x-model="valori.da" :max="valori.a || null" class="input">
                    </div>
                    <div>
                        <label class="label">Al</label>
                        <input type="date" x-model="valori.a" :min="valori.da || null" class="input">
                    </div>
                </div>
            </x-ui.filtri>
        </section>

        @if ($vista === 'clienti')
            <section class="overflow-hidden rounded-3xl border border-sky-100 bg-white shadow-sm">
                <div class="border-b border-sky-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-800">Ore per cliente</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $perCliente->count() }} clienti</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sky-50">
                        <thead class="bg-sky-50/70">
                            <tr><th class="th">Cliente</th><th class="th">Ore previste</th><th class="th">Ore lavorate</th><th class="th">Saldo</th><th class="th">Scarica</th></tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50">
                            @forelse ($perCliente as $idCliente => $cliente)
                                @php
                                    $saldo = $cliente['lavorate'] - $cliente['previste'];
                                @endphp
                                <tr class="transition hover:bg-sky-50/40">
                                    <td class="td font-medium">{{ $cliente['nome'] }}</td>
                                    <td class="td">{{ $cliente['previste'] }}</td>
                                    <td class="td">{{ $cliente['lavorate'] }}</td>
                                    <td @class(['td font-semibold', 'text-rose-600' => $saldo < 0, 'text-emerald-600' => $saldo > 0])>{{ $saldo }}</td>
                                    <td class="td"><a href="{{ route('presenze.ore', ['tipo' => 'cliente', 'id' => $idCliente, 'da' => $da, 'a' => $a, 'sede' => $filtroSede]) }}" class="btn-soft">Scarica</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-14 text-center">
                                    <p class="font-semibold text-slate-700">Nessun cliente trovato</p>
                                    <p class="mt-1 text-sm text-slate-500">Modifica i filtri per visualizzare altri risultati.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section class="overflow-hidden rounded-3xl border border-sky-100 bg-white shadow-sm">
                <div class="border-b border-sky-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-800">Ore per operatore</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $perOperatore->count() }} operatori</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sky-50">
                        <thead class="bg-sky-50/70">
                            <tr><th class="th">Codice</th><th class="th">Operatore</th><th class="th">Ore previste</th><th class="th">Ore lavorate</th><th class="th">Saldo</th><th class="th">Scarica</th></tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50">
                            @forelse ($perOperatore as $idOperatore => $operatore)
                                @php
                                    $saldo = $operatore['lavorate'] - $operatore['previste'];
                                @endphp
                                <tr class="transition hover:bg-sky-50/40">
                                    <td class="td">{{ $operatore['codice'] }}</td>
                                    <td class="td font-medium">{{ $operatore['nome'] }}</td>
                                    <td class="td">{{ $operatore['previste'] }}</td>
                                    <td class="td">{{ $operatore['lavorate'] }}</td>
                                    <td @class(['td font-semibold', 'text-rose-600' => $saldo < 0, 'text-emerald-600' => $saldo > 0])>{{ $saldo }}</td>
                                    <td class="td"><a href="{{ route('presenze.ore', ['tipo' => 'operatore', 'id' => $idOperatore, 'da' => $da, 'a' => $a]) }}" class="btn-soft">Scarica</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-14 text-center">
                                    <p class="font-semibold text-slate-700">Nessun operatore trovato</p>
                                    <p class="mt-1 text-sm text-slate-500">Modifica i filtri per visualizzare altri risultati.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endif

@php($staff = auth()->user()->isStaff())
<div class="space-y-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:flex-1">
            @if ($vista === 'lista')
                <div><label class="label">Dal</label><input type="date" wire:model.live="da" class="input"></div>
                <div><label class="label">Al</label><input type="date" wire:model.live="a" class="input"></div>
            @endif
            @unless (auth()->user()->isOperatore())
                <div class="relative space-y-1" x-data="{
                    aperto: false,
                    ricerca: '',
                    opzioni: @js($suggerimentiSedi),
                    filtrati() {
                        const termine = this.ricerca.trim().toLocaleLowerCase('it');
                        return this.opzioni.filter(opzione => opzione.search.toLocaleLowerCase('it').includes(termine)).slice(0, 20);
                    },
                    seleziona(id) {
                        $wire.selezionaSuggerimento('sede', id);
                        this.aperto = false;
                        this.ricerca = '';
                    }
                }" @click.outside="aperto = false" @keydown.escape.window="aperto = false">
                    <label class="label" for="filtro-sede">Sede</label>
                    <button id="filtro-sede" type="button" role="combobox" aria-controls="suggerimenti-sedi" :aria-expanded="aperto" @click="aperto = !aperto; if (aperto) $nextTick(() => $refs.ricerca.focus())" class="input flex items-center justify-between text-left">
                        <span class="{{ $sedeSelezionata ? 'text-slate-800' : 'text-slate-500' }}">{{ $sedeSelezionata?->descrizione ?? 'Tutte le sedi' }}</span>
                        <span aria-hidden="true" class="text-slate-400">⌄</span>
                    </button>
                    <div x-cloak x-show="aperto" class="absolute inset-x-0 top-full z-30 mt-1 overflow-hidden rounded-xl border border-sky-100 bg-white shadow-lg">
                        <div class="border-b border-sky-100 p-2">
                            <input x-ref="ricerca" x-model="ricerca" type="search" aria-label="Cerca una sede" placeholder="Digita per cercare…" @keydown.enter.prevent="const opzione = filtrati()[0]; if (opzione) seleziona(opzione.id)" class="input">
                        </div>
                        <ul id="suggerimenti-sedi" role="listbox" class="max-h-60 overflow-y-auto p-1">
                            <li role="option">
                                <button type="button" @click="$wire.rimuoviFiltro('sede'); aperto = false; ricerca = ''" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-sky-50">Tutte le sedi</button>
                            </li>
                            <template x-for="opzione in filtrati()" :key="opzione.id">
                                <li role="option">
                                    <button type="button" @click="seleziona(opzione.id)" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">
                                        <span class="block font-semibold text-slate-800" x-text="opzione.label"></span>
                                        <span class="block text-xs text-slate-500" x-text="opzione.detail"></span>
                                    </button>
                                </li>
                            </template>
                            <li x-show="filtrati().length === 0" class="px-3 py-2 text-sm text-slate-500">Nessuna sede trovata.</li>
                        </ul>
                    </div>
                </div>
                <div class="relative space-y-1" x-data="{
                    aperto: false,
                    ricerca: '',
                    opzioni: @js($suggerimentiOperatori),
                    filtrati() {
                        const termine = this.ricerca.trim().toLocaleLowerCase('it');
                        return this.opzioni.filter(opzione => opzione.search.toLocaleLowerCase('it').includes(termine)).slice(0, 20);
                    },
                    seleziona(id) {
                        $wire.selezionaSuggerimento('operatore', id);
                        this.aperto = false;
                        this.ricerca = '';
                    }
                }" @click.outside="aperto = false" @keydown.escape.window="aperto = false">
                    <label class="label" for="filtro-operatore">Operatore</label>
                    <button id="filtro-operatore" type="button" role="combobox" aria-controls="suggerimenti-operatori" :aria-expanded="aperto" @click="aperto = !aperto; if (aperto) $nextTick(() => $refs.ricerca.focus())" class="input flex items-center justify-between text-left">
                        <span class="{{ $operatoreSelezionato ? 'text-slate-800' : 'text-slate-500' }}">{{ $operatoreSelezionato?->nome_completo ?? 'Tutti gli operatori' }}</span>
                        <span aria-hidden="true" class="text-slate-400">⌄</span>
                    </button>
                    <div x-cloak x-show="aperto" class="absolute inset-x-0 top-full z-30 mt-1 overflow-hidden rounded-xl border border-sky-100 bg-white shadow-lg">
                        <div class="border-b border-sky-100 p-2">
                            <input x-ref="ricerca" x-model="ricerca" type="search" aria-label="Cerca un operatore" placeholder="Digita per cercare…" @keydown.enter.prevent="const opzione = filtrati()[0]; if (opzione) seleziona(opzione.id)" class="input">
                        </div>
                        <ul id="suggerimenti-operatori" role="listbox" class="max-h-60 overflow-y-auto p-1">
                            <li role="option">
                                <button type="button" @click="$wire.rimuoviFiltro('operatore'); aperto = false; ricerca = ''" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-sky-50">Tutti gli operatori</button>
                            </li>
                            <template x-for="opzione in filtrati()" :key="opzione.id">
                                <li role="option">
                                    <button type="button" @click="seleziona(opzione.id)" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">
                                        <span class="block font-semibold text-slate-800" x-text="opzione.label"></span>
                                        <span class="block text-xs text-slate-500" x-text="opzione.detail"></span>
                                    </button>
                                </li>
                            </template>
                            <li x-show="filtrati().length === 0" class="px-3 py-2 text-sm text-slate-500">Nessun operatore trovato.</li>
                        </ul>
                    </div>
                </div>
                @if ($staff)
                    <div>
                        <label class="label" for="filtro-cliente">Cliente</label>
                        <select id="filtro-cliente" wire:model.live="filtroCliente" class="input"><option value="">Tutti i clienti</option>@foreach ($clienti as $c)<option value="{{ $c->id }}">{{ $c->ragione_sociale }}</option>@endforeach</select>
                    </div>
                @endif
                <div>
                    <label class="label" for="filtro-stato">Stato</label>
                    <select id="filtro-stato" wire:model.live="filtroStato" class="input">
                        <option value="">Tutti gli stati</option>
                        @foreach (\App\Models\Legacy\Turno::etichette() as $chiave => [$etichettaStato])<option value="{{ $chiave }}">{{ $etichettaStato }}</option>@endforeach
                    </select>
                </div>
            @endunless
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-xl bg-white p-1 ring-1 ring-sky-200">
                <button wire:click="$set('vista', 'calendario')" class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $vista === 'calendario' ? 'bg-sky-500 text-white' : 'text-slate-600' }}">Calendario</button>
                <button wire:click="$set('vista', 'lista')" class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $vista === 'lista' ? 'bg-sky-500 text-white' : 'text-slate-600' }}">Lista</button>
            </div>
            @if ($staff) <button wire:click="nuovoInvio" class="btn-soft">✉ Invia turni</button> <button wire:click="nuovo" class="btn-primary">+ Nuovo turno</button> @endif
        </div>
    </div>

    @if ($vista === 'calendario')
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="inline-flex w-fit rounded-xl bg-white p-1 ring-1 ring-sky-200">
                @foreach (['mese' => 'Mese', 'settimana' => 'Settimana', 'giorno' => 'Giorno'] as $periodo => $etichetta)
                    <button wire:click="cambiaPeriodoCalendario('{{ $periodo }}')" class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $periodoCalendario === $periodo ? 'bg-sky-500 text-white' : 'text-slate-600' }}">{{ $etichetta }}</button>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                @if ($periodoCalendario === 'mese')
                    <div>
                        <label for="selezione-mese" class="sr-only">Scegli un mese</label>
                        <input id="selezione-mese" type="month" wire:model.live="selezioneMese" class="input w-auto">
                    </div>
                @elseif ($periodoCalendario === 'settimana')
                    <div>
                        <label for="selezione-data" class="sr-only">Scegli una data della settimana</label>
                        <input id="selezione-data" type="date" wire:model.live="selezioneData" class="input w-auto" aria-label="Settimana del">
                    </div>
                @else
                    <div>
                        <label for="selezione-data" class="sr-only">Scegli un giorno</label>
                        <input id="selezione-data" type="date" wire:model.live="selezioneData" class="input w-auto">
                    </div>
                @endif
                <button wire:click="cambiaPeriodo(-1)" class="btn-soft !px-3" aria-label="Periodo precedente">←</button>
                <span class="min-w-48 text-center text-base font-bold capitalize text-slate-800">
                    @if ($periodoCalendario === 'mese')
                        {{ $calendario['primo']->locale('it')->translatedFormat('F Y') }}
                    @elseif ($periodoCalendario === 'settimana')
                        {{ $calendario['da']->locale('it')->translatedFormat('d M') }} – {{ $calendario['a']->locale('it')->translatedFormat('d M Y') }}
                    @else
                        {{ $calendario['da']->locale('it')->translatedFormat('l d F Y') }}
                    @endif
                </span>
                <button wire:click="cambiaPeriodo(1)" class="btn-soft !px-3" aria-label="Periodo successivo">→</button>
                <button wire:click="oggi" class="btn-soft">Oggi</button>
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="grid {{ $periodoCalendario === 'giorno' ? 'grid-cols-1' : 'grid-cols-7' }} border-b border-sky-100 bg-sky-50/60 text-center">
                @if ($periodoCalendario === 'giorno')
                    <div class="py-2 text-xs font-semibold uppercase text-slate-500">{{ $calendario['da']->locale('it')->translatedFormat('l d/m') }}</div>
                @else
                    @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $g)<div class="py-2 text-xs font-semibold uppercase text-slate-500">{{ $g }}</div>@endforeach
                @endif
            </div>
            <div class="grid {{ $periodoCalendario === 'giorno' ? 'grid-cols-1' : 'grid-cols-7' }}">
                @for ($d = $calendario['da']->copy(); $d->lte($calendario['a']); $d->addDay())
                    @php($k = $d->toDateString())
                    @php($giorno = $calendario['turni']->get($k, collect()))
                    <div wire:key="g{{ $k }}" class="group min-h-24 border-b border-r border-sky-50 p-1 sm:min-h-32 sm:p-1.5 {{ $periodoCalendario === 'giorno' ? 'min-h-48 sm:min-h-64' : '' }} {{ $periodoCalendario === 'mese' && $d->month !== $calendario['primo']->month ? 'bg-slate-50/60' : '' }}">
                        <div class="mb-1 flex items-center justify-between">
                            <button wire:click="vaiAlGiorno('{{ $k }}')" class="flex h-6 min-w-6 items-center justify-center rounded-full px-1 text-xs font-semibold {{ $d->isToday() ? 'bg-sky-500 text-white' : ($periodoCalendario === 'mese' && $d->month !== $calendario['primo']->month ? 'text-slate-400' : 'text-slate-700') }}" aria-label="Mostra il {{ $d->locale('it')->translatedFormat('d F Y') }}">{{ $d->day }}</button>
                            @if ($staff)<button wire:click="nuovoGiorno('{{ $k }}')" class="hidden h-5 w-5 rounded text-sky-600 hover:bg-sky-100 group-hover:block" title="Aggiungi turno">+</button>@endif
                        </div>
                        <div class="space-y-1">
                            @foreach ($periodoCalendario === 'mese' ? $giorno->take(3) : $giorno as $t)
                                @php($c = ['programmato' => 'bg-sky-100 text-sky-800', 'in_corso' => 'bg-emerald-100 text-emerald-800', 'completato' => 'bg-teal-100 text-teal-800', 'non_timbrato' => 'bg-amber-100 text-amber-800', 'non_completato' => 'bg-orange-100 text-orange-800', 'non_eseguito' => 'bg-rose-100 text-rose-800'][$t->statoEffettivo()])
                                <button type="button" wire:click="mostraTurno({{ $t->id }})" wire:key="turno-{{ $t->id }}" class="flex w-full min-w-0 items-center justify-between gap-1 rounded-md px-1.5 py-1 text-left text-[11px] font-medium leading-tight hover:ring-1 hover:ring-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500 {{ $c }}" title="{{ $t->sede?->descrizione }} · {{ $t->operatore?->nome_completo }}" aria-label="Visualizza timbrature turno delle {{ $t->inizio->format('H:i') }} presso {{ $t->sede?->descrizione }} per {{ $t->operatore?->nome_completo }}">
                                    <span class="min-w-0 truncate"><span class="font-bold">{{ $t->inizio->format('H:i') }}</span> <span class="hidden sm:inline">{{ $t->operatore?->cognome ?: $t->operatore?->nome }}</span></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                </button>
                            @endforeach
                            @if ($periodoCalendario === 'mese' && $giorno->count() > 3)
                                <button wire:click="vaiAlGiorno('{{ $k }}')" class="px-1 text-[11px] font-semibold text-sky-600 hover:underline">+{{ $giorno->count() - 3 }} altri</button>
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    @if ($vista === 'lista' && $totali)
        <div class="card p-5"><p class="label">Totale ore previste ({{ $totali['etichetta'] }})</p><p class="text-2xl font-bold text-sky-600">{{ $totali['ore'] }}</p></div>
    @endif

    @if ($vista === 'lista')
    <div class="card overflow-hidden"><div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sky-100">
            <thead class="bg-sky-50/60"><tr><th class="th">Ragione Sociale</th><th class="th">Sede</th><th class="th">Operatore</th><th class="th">Inizio Turno</th><th class="th">Fine Turno</th><th class="th text-center">Dettaglio</th><th class="th">Ore</th><th class="th">Stato</th>@if ($staff)<th class="th text-center">Inizia</th><th class="th text-center">Chiudi</th><th class="th text-center">Mod</th><th class="th text-center">Can</th>@endif</tr></thead>
            <tbody class="divide-y divide-sky-50">
                @forelse ($turni as $t)
                    @php([$et, $cl] = \App\Models\Legacy\Turno::etichette()[$t->statoEffettivo()])
                    <tr wire:key="t{{ $t->id }}" class="hover:bg-sky-50/40">
                        <td class="td font-semibold text-slate-800">{{ $t->sede?->cliente?->ragione_sociale }}</td>
                        <td class="td">{{ $t->sede?->descrizione }}</td>
                        <td class="td">{{ $t->operatore?->nome_completo }}</td>
                        <td class="td whitespace-nowrap">{{ $t->inizio->format('d/m/Y H:i') }}</td>
                        <td class="td whitespace-nowrap">{{ $t->fine->format('d/m/Y H:i') }}</td>
                        <td class="td text-center">
                            <button type="button" wire:click="mostraTurno({{ $t->id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-sky-700 transition hover:bg-sky-100 hover:text-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500" aria-label="Visualizza timbrature turno delle {{ $t->inizio->format('H:i') }} presso {{ $t->sede?->descrizione }}" title="Visualizza timbrature">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                    <circle cx="12" cy="12" r="2.5" />
                                </svg>
                            </button>
                        </td>
                        <td class="td">{{ $t->ore }}@if ($t->ore_lavorate !== null) <span class="text-xs text-slate-500">({{ $t->ore_lavorate }} lav.)</span>@endif</td>
                        <td class="td"><span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $cl }}">{{ $et }}</span></td>
                        @if ($staff)
                            <td class="td text-center">@if ($t->stato === 'P' && $t->inizio->lt(now()))<button wire:click="apriChiudi({{ $t->id }})" class="btn-soft !px-3 !py-1.5">Inizia</button>@endif</td>
                            <td class="td text-center">@if ($t->stato === 'I' && $t->fine->lt(now()))<button wire:click="apriChiudi({{ $t->id }})" class="btn-soft !px-3 !py-1.5">Chiudi</button>@endif</td>
                            <td class="td text-center"><button wire:click="modifica({{ $t->id }})" class="btn-soft !px-3 !py-1.5">Mod</button></td>
                            <td class="td text-center"><button wire:click="elimina({{ $t->id }})" wire:confirm="Eliminare il turno?" class="btn-danger !px-3 !py-1.5">Can</button></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $staff ? 12 : 8 }}" class="td py-10 text-center text-slate-500">Nessun turno nel periodo selezionato.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
    {{ $turni->links() }}
    @endif

    @if ($modal)
        <x-ui.modal title="Nuovo turno" wide>
            <form wire:submit="salva" class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Cliente" name="f.cliente_id"><select wire:model.live="f.cliente_id" class="input"><option value="">—</option>@foreach ($clienti as $c)<option value="{{ $c->id }}">{{ $c->ragione_sociale }}</option>@endforeach</select></x-ui.field>
                <x-ui.field label="Sede" name="f.sede_id"><select wire:model="f.sede_id" class="input"><option value="">—</option>@foreach ($sediForm as $s)<option value="{{ $s->id }}">{{ $s->descrizione }}</option>@endforeach</select></x-ui.field>
                <x-ui.field label="Data inizio" name="f.data_inizio"><input type="date" wire:model.live="f.data_inizio" class="input"></x-ui.field>
                <x-ui.field label="Ora inizio" name="f.ora_inizio"><input type="time" wire:model.live="f.ora_inizio" class="input"></x-ui.field>
                <x-ui.field label="Data fine" name="f.data_fine"><input type="date" wire:model.live="f.data_fine" class="input"></x-ui.field>
                <x-ui.field label="Ora fine" name="f.ora_fine"><input type="time" wire:model.live="f.ora_fine" class="input"></x-ui.field>
                <x-ui.field label="Operatore (solo disponibili)" name="f.operatore_id" class="sm:col-span-2">
                    <select wire:model="f.operatore_id" class="input"><option value="">—</option>@foreach ($disponibili as $o)<option value="{{ $o->id }}">{{ $o->preferito ? '★ ' : '' }}{{ $o->nome_completo }}</option>@endforeach</select>
                </x-ui.field>
                <div class="rounded-2xl bg-sky-50 p-4 sm:col-span-2">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Ripeti turno (facoltativo)</p>
                    <div class="mb-3 flex flex-wrap gap-2">
                        @foreach ($giorniSettimana as $n => $g)
                            <label class="cursor-pointer"><input type="checkbox" wire:model="giorni" value="{{ $n }}" class="peer sr-only"><span class="block rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-600 ring-1 ring-sky-200 peer-checked:bg-sky-500 peer-checked:text-white">{{ $g }}</span></label>
                        @endforeach
                    </div>
                    <x-ui.field label="Fino al" name="f.ripeti_fino"><input type="date" wire:model="f.ripeti_fino" class="input sm:max-w-xs"></x-ui.field>
                </div>
                <x-ui.field label="Note" name="f.note" class="sm:col-span-2"><textarea wire:model="f.note" rows="2" class="input"></textarea></x-ui.field>
                @if ($esito) <p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800 sm:col-span-2">{{ $esito }}</p> @endif
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Chiudi</button>
                    <button class="btn-primary">Crea turno</button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($ac)
        <x-ui.modal title="{{ $ac['stato'] === 'I' ? 'Inizia turno' : 'Chiudi turno' }}" close="annullaApriChiudi">
            <form wire:submit="confermaApriChiudi" class="space-y-4">
                <x-ui.field label="Note" name="ac.note"><textarea wire:model="ac.note" rows="4" class="input" placeholder="Nota di inizio / chiusura turno"></textarea></x-ui.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="{{ $ac['stato'] === 'I' ? 'Data inizio' : 'Data fine' }}" name="ac.data"><input type="date" wire:model="ac.data" class="input"></x-ui.field>
                    <x-ui.field label="{{ $ac['stato'] === 'I' ? 'Ora inizio' : 'Ora fine' }}" name="ac.ora"><input type="time" wire:model="ac.ora" class="input"></x-ui.field>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="annullaApriChiudi" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Conferma</button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($modalInvio)
        <x-ui.modal title="Invia turni agli operatori" close="chiudiInvio" wide>
            <form wire:submit="inviaTurni" class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Cliente" name="invio.cliente"><select wire:model="invio.cliente" class="input"><option value="">Tutti</option>@foreach ($clienti as $c)<option value="{{ $c->id }}">{{ $c->ragione_sociale }}</option>@endforeach</select></x-ui.field>
                <x-ui.field label="Sede" name="invio.sede"><select wire:model="invio.sede" class="input"><option value="">Tutte</option>@foreach ($sediInvio as $s)<option value="{{ $s->id }}">{{ $s->descrizione }}</option>@endforeach</select></x-ui.field>
                <x-ui.field label="Operatore" name="invio.operatore" class="sm:col-span-2"><select wire:model="invio.operatore" class="input"><option value="">Tutti</option>@foreach ($operatoriInvio as $o)<option value="{{ $o->id }}">{{ $o->nome_completo }}</option>@endforeach</select></x-ui.field>
                <x-ui.field label="Turni dal" name="invio.inizio"><input type="date" wire:model="invio.inizio" class="input"></x-ui.field>
                <x-ui.field label="Turni al" name="invio.fine"><input type="date" wire:model="invio.fine" class="input"></x-ui.field>
                <x-ui.field label="Testo email" name="invio.testo" class="sm:col-span-2"><textarea wire:model="invio.testo" rows="3" class="input"></textarea></x-ui.field>
                @if ($esitoInvio) <p class="rounded-xl bg-sky-50 p-3 text-sm text-slate-700 sm:col-span-2">{{ $esitoInvio }}</p> @endif
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" wire:click="chiudiInvio" class="btn-soft">Chiudi</button>
                    <button class="btn-primary">Invia</button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($turnoDettaglio)
        @php([$etichettaStato, $classeStato] = \App\Models\Legacy\Turno::etichette()[$turnoDettaglio->statoEffettivo()])
        <x-ui.modal title="Dettaglio turno" close="chiudiDettaglioTurno">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Data</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->inizio->locale('it')->translatedFormat('l d F Y') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Orario</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->inizio->format('H:i') }}–{{ $turnoDettaglio->fine->format('H:i') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Sede</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->sede?->descrizione }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Cliente</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->sede?->cliente?->ragione_sociale }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Operatore</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->operatore?->nome_completo }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Stato</dt><dd class="mt-1"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $classeStato }}">{{ $etichettaStato }}</span></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Ore previste</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->ore }}</dd></div>
                @if ($turnoDettaglio->ore_lavorate !== null)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Ore lavorate</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoDettaglio->ore_lavorate }}</dd></div>
                @endif
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Note</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $turnoDettaglio->note ?: 'Nessuna nota' }}</dd></div>
            </dl>
            <div class="mt-5">
                <x-turni.progressione :turno="$turnoDettaglio" />
            </div>
            <div class="mt-5 flex justify-end gap-2">@if ($staff)<button type="button" wire:click="modifica({{ $turnoDettaglio->id }})" class="btn-primary">Modifica</button>@endif<button type="button" wire:click="chiudiDettaglioTurno" class="btn-soft">Chiudi</button></div>
        </x-ui.modal>
    @endif
    @if ($turnoModifica)
        <x-ui.modal title="Modifica turno" close="chiudiModifica" wide>
            <form wire:submit="aggiorna" class="space-y-4">
                <dl class="grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Ragione sociale</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoModifica->sede?->cliente?->ragione_sociale }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Sede</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoModifica->sede?->descrizione }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Operatore</dt><dd class="mt-1 font-medium text-slate-800">{{ $turnoModifica->operatore?->nome_completo }}</dd></div>
                </dl>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Data inizio" name="m.data_inizio"><input type="date" wire:model="m.data_inizio" class="input"></x-ui.field>
                    <x-ui.field label="Ora inizio" name="m.ora_inizio"><input type="time" wire:model="m.ora_inizio" class="input"></x-ui.field>
                    <x-ui.field label="Data fine" name="m.data_fine"><input type="date" wire:model="m.data_fine" class="input"></x-ui.field>
                    <x-ui.field label="Ora fine" name="m.ora_fine"><input type="time" wire:model="m.ora_fine" class="input"></x-ui.field>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="chiudiModifica" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Salva</button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>

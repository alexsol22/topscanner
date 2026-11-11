@php($staff = auth()->user()->isStaff())
<div class="space-y-5">
    <h1 class="text-2xl font-bold tracking-tight text-slate-800">
        Agenda Pos
        @if ($clienteSelezionato)
            <span class="text-base font-semibold">Cliente: <span class="text-sky-600">{{ $clienteSelezionato->ragione_sociale }}</span></span>
        @endif
        @if ($sedeSelezionata)
            <span class="text-base font-semibold">Sede: <span class="text-sky-600">{{ $sedeSelezionata->descrizione }}</span></span>
        @endif
        @if ($operatoreSelezionato)
            <span class="text-base font-semibold">Operatore: <span class="text-sky-600">{{ $operatoreSelezionato->nome_completo }}</span></span>
        @endif
        @if ($filtroStato !== '' && isset(\App\Models\Legacy\Turno::etichette()[$filtroStato]))
            <span class="text-base font-semibold">Stato: <span class="text-sky-600">{{ \App\Models\Legacy\Turno::etichette()[$filtroStato][0] }}</span></span>
        @endif
        @if ($totaleOreAgenda !== null)
            <span class="text-base font-semibold">Totale ore: <span class="text-sky-600">{{ number_format($totaleOreAgenda, 2, '.', '') }}</span></span>
            <span class="text-xs font-medium text-slate-500">{{ $da || $a ? '(intervallo selezionato)' : '(tutti i periodi)' }}</span>
        @endif
    </h1>
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-sky-200 bg-white p-3 shadow-sm sm:p-4">
      <div class="flex min-w-0 flex-wrap items-center gap-2">
        <x-ui.filtri :values="array_merge(
            ['da' => $da, 'a' => $a, 'filtroStato' => $filtroStato],
            ! auth()->user()->isOperatore() ? ['filtroSede' => $filtroSede, 'filtroOperatore' => $filtroOperatore] : [],
            $staff ? ['filtroCliente' => $filtroCliente] : [],
        )">
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="label" for="filtro-stato">Stato turno</label>
                <select id="filtro-stato" x-model="valori.filtroStato" class="input">
                    <option value="">Scegli</option>
                    @foreach (\App\Models\Legacy\Turno::etichette() as $chiave => [$etichettaStato])<option value="{{ $chiave }}">{{ $etichettaStato }}</option>@endforeach
                </select>
            </div>
            @unless (auth()->user()->isOperatore())
                @if ($staff)
                    <div>
                        <label class="label">Cliente</label>
                        <x-select-ricerca model="filtroCliente" :draft="true" :options="$clienti->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()" placeholder="Tutti i clienti" />
                    </div>
                @endif
                <div class="relative space-y-1" x-data="{
                    aperto: false,
                    ricerca: '',
                    opzioni: @js($suggerimentiSedi),
                    stileMenu: {},
                    altezzaLista: '15rem',
                    filtrati() {
                        const termine = this.ricerca.trim().toLocaleLowerCase('it');
                        return this.opzioni.filter(opzione => opzione.search.toLocaleLowerCase('it').includes(termine)).slice(0, 20);
                    },
                    apri() {
                        this.aperto = !this.aperto;
                        if (this.aperto) {
                            this.$nextTick(() => {
                                this.aggiornaPosizione();
                                this.$refs.ricerca.focus();
                            });
                        }
                    },
                    aggiornaPosizione() {
                        const rettangolo = this.$refs.selettore.getBoundingClientRect();
                        const spazioSotto = window.innerHeight - rettangolo.bottom - 8;
                        const spazioSopra = rettangolo.top - 8;
                        const sopra = spazioSotto < 320 && spazioSopra > spazioSotto;
                        const spazioDisponibile = sopra ? spazioSopra : spazioSotto;

                        this.stileMenu = {
                            left: `${rettangolo.left}px`,
                            width: `${rettangolo.width}px`,
                            top: sopra ? 'auto' : `${rettangolo.bottom + 4}px`,
                            bottom: sopra ? `${window.innerHeight - rettangolo.top + 4}px` : 'auto',
                        };
                        this.altezzaLista = `${Math.max(48, Math.min(240, spazioDisponibile - 72))}px`;
                    },
                    seleziona(id) {
                        this.valori.filtroSede = id;
                        this.aperto = false;
                        this.ricerca = '';
                    }
                }" @click.outside="aperto = false" @keydown.escape="if (aperto) { $event.stopPropagation(); aperto = false; }" @scroll.window.capture="if (aperto) aggiornaPosizione()" @resize.window="if (aperto) aggiornaPosizione()">
                    <label class="label" for="filtro-sede">Sede</label>
                    <button id="filtro-sede" x-ref="selettore" type="button" role="combobox" aria-controls="suggerimenti-sedi" :aria-expanded="aperto" @click="apri()" class="input flex items-center justify-between text-left">
                        <span x-text="opzioni.find(opzione => String(opzione.id) === String(valori.filtroSede))?.label ?? 'Tutte le sedi'"></span>
                        <span aria-hidden="true" class="text-slate-400">⌄</span>
                    </button>
                    <div x-cloak x-show="aperto" x-bind:style="stileMenu" class="fixed z-[60] min-w-48 overflow-hidden rounded-xl border border-sky-100 bg-white shadow-lg">
                        <div class="border-b border-sky-100 p-2">
                            <input x-ref="ricerca" x-model="ricerca" type="search" aria-label="Cerca una sede" placeholder="Digita per cercare…" @keydown.enter.prevent="const opzione = filtrati()[0]; if (opzione) seleziona(opzione.id)" class="input">
                        </div>
                        <ul id="suggerimenti-sedi" x-bind:style="`max-height: ${altezzaLista}`" role="listbox" class="overflow-y-auto p-1">
                            <li role="option">
                                <button type="button" @click="valori.filtroSede = null; aperto = false; ricerca = ''" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-sky-50">Tutte le sedi</button>
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
                    stileMenu: {},
                    altezzaLista: '15rem',
                    filtrati() {
                        const termine = this.ricerca.trim().toLocaleLowerCase('it');
                        return this.opzioni.filter(opzione => opzione.search.toLocaleLowerCase('it').includes(termine)).slice(0, 20);
                    },
                    apri() {
                        this.aperto = !this.aperto;
                        if (this.aperto) {
                            this.$nextTick(() => {
                                this.aggiornaPosizione();
                                this.$refs.ricerca.focus();
                            });
                        }
                    },
                    aggiornaPosizione() {
                        const rettangolo = this.$refs.selettore.getBoundingClientRect();
                        const spazioSotto = window.innerHeight - rettangolo.bottom - 8;
                        const spazioSopra = rettangolo.top - 8;
                        const sopra = spazioSotto < 320 && spazioSopra > spazioSotto;
                        const spazioDisponibile = sopra ? spazioSopra : spazioSotto;

                        this.stileMenu = {
                            left: `${rettangolo.left}px`,
                            width: `${rettangolo.width}px`,
                            top: sopra ? 'auto' : `${rettangolo.bottom + 4}px`,
                            bottom: sopra ? `${window.innerHeight - rettangolo.top + 4}px` : 'auto',
                        };
                        this.altezzaLista = `${Math.max(48, Math.min(240, spazioDisponibile - 72))}px`;
                    },
                    seleziona(id) {
                        this.valori.filtroOperatore = id;
                        this.aperto = false;
                        this.ricerca = '';
                    }
                }" @click.outside="aperto = false" @keydown.escape="if (aperto) { $event.stopPropagation(); aperto = false; }" @scroll.window.capture="if (aperto) aggiornaPosizione()" @resize.window="if (aperto) aggiornaPosizione()">
                    <label class="label" for="filtro-operatore">Operatore</label>
                    <button id="filtro-operatore" x-ref="selettore" type="button" role="combobox" aria-controls="suggerimenti-operatori" :aria-expanded="aperto" @click="apri()" class="input flex items-center justify-between text-left">
                        <span x-text="opzioni.find(opzione => String(opzione.id) === String(valori.filtroOperatore))?.label ?? 'Tutti gli operatori'"></span>
                        <span aria-hidden="true" class="text-slate-400">⌄</span>
                    </button>
                    <div x-cloak x-show="aperto" x-bind:style="stileMenu" class="fixed z-[60] min-w-48 overflow-hidden rounded-xl border border-sky-100 bg-white shadow-lg">
                        <div class="border-b border-sky-100 p-2">
                            <input x-ref="ricerca" x-model="ricerca" type="search" aria-label="Cerca un operatore" placeholder="Digita per cercare…" @keydown.enter.prevent="const opzione = filtrati()[0]; if (opzione) seleziona(opzione.id)" class="input">
                        </div>
                        <ul id="suggerimenti-operatori" x-bind:style="`max-height: ${altezzaLista}`" role="listbox" class="overflow-y-auto p-1">
                            <li role="option">
                                <button type="button" @click="valori.filtroOperatore = null; aperto = false; ricerca = ''" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-sky-50">Tutti gli operatori</button>
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
            @endunless
            <div><label class="label" for="filtro-dal">Dal</label><input id="filtro-dal" type="date" x-model="valori.da" :max="valori.a || null" class="input"></div>
            <div><label class="label" for="filtro-al">Al</label><input id="filtro-al" type="date" x-model="valori.a" :min="valori.da || null" class="input"></div>
        </div>
        </x-ui.filtri>
      @if ($vista === 'calendario')
        <div class="flex shrink-0 items-center gap-1.5" role="group" aria-label="Filtra turni per stato">
            <button
                type="button"
                wire:click="$set('filtroStato', '')"
                aria-label="Azzera filtro stato"
                title="Azzera filtro stato"
                class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400"
            >Azzera</button>
            @foreach (\App\Models\Legacy\Turno::etichette() as $chiave => [$etichettaStato])
                <button
                    type="button"
                    wire:click="$set('filtroStato', '{{ $filtroStato === $chiave ? '' : $chiave }}')"
                    aria-label="Filtra turni: {{ $etichettaStato }}"
                    title="{{ $etichettaStato }}"
                    aria-pressed="{{ $filtroStato === $chiave ? 'true' : 'false' }}"
                    @class([
                        'size-7 shrink-0 cursor-pointer rounded-full transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2',
                        'bg-sky-500 focus:ring-sky-500' => $chiave === 'programmato',
                        'bg-emerald-500 focus:ring-emerald-500' => $chiave === 'in_corso',
                        'bg-teal-500 focus:ring-teal-500' => $chiave === 'completato',
                        'bg-amber-500 focus:ring-amber-500' => $chiave === 'non_timbrato',
                        'bg-orange-500 focus:ring-orange-500' => $chiave === 'non_completato',
                        'bg-rose-500 focus:ring-rose-500' => $chiave === 'non_eseguito',
                        'ring-2 ring-offset-2' => $filtroStato === $chiave,
                    ])
                ></button>
            @endforeach
        </div>
      @endif
      </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-xl bg-white p-1 ring-1 ring-sky-200">
                <button wire:click="$set('vista', 'calendario')" class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $vista === 'calendario' ? 'bg-sky-500 text-white' : 'text-slate-600' }}">Calendario</button>
                <button wire:click="$set('vista', 'lista')" class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $vista === 'lista' ? 'bg-sky-500 text-white' : 'text-slate-600' }}">Lista</button>
            </div>
            @if ($staff) <button wire:click="nuovoInvio" class="btn-soft">✉ Invia turni</button> <button wire:click="nuovo" class="btn-primary">+ Nuovo turno</button> @endif
            @if (auth()->user()->isOperatore())
                <a href="{{ route('scansione', ['sostituzione' => 1]) }}" wire:navigate class="btn-primary">Sostituisci turno</a>
            @endif
        </div>
    </div>

    @if ($sedeSelezionata && $copertura !== [])
        <section class="rounded-2xl border border-sky-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-slate-800">Copertura settimanale</h2>
                    <p class="truncate text-xs text-slate-500">
                        {{ $copertura[0]['giorno']->locale('it')->translatedFormat('d M') }} – {{ $copertura[6]['giorno']->locale('it')->translatedFormat('d M Y') }}
                        · ore programmate su ore richieste
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" wire:click="cambiaSettimanaCopertura(-1)" class="btn-soft !px-2.5" aria-label="Settimana precedente">←</button>
                    <button type="button" wire:click="cambiaSettimanaCopertura(1)" class="btn-soft !px-2.5" aria-label="Settimana successiva">→</button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($copertura as $giornoCopertura)
                    <div
                        wire:key="copertura-{{ $giornoCopertura['giorno']->toDateString() }}"
                        role="group"
                        aria-label="{{ $giornoCopertura['etichetta'] }}"
                        @class([
                            'rounded-xl px-2.5 py-2',
                            'bg-emerald-50 ring-1 ring-emerald-200' => $giornoCopertura['stato'] === 'ok' && $giornoCopertura['richieste'] > 0,
                            'bg-slate-50 ring-1 ring-slate-200' => $giornoCopertura['richieste'] <= 0,
                            'bg-amber-50 ring-1 ring-amber-200' => $giornoCopertura['stato'] === 'scoperto',
                            'bg-sky-100 ring-1 ring-sky-300' => $giornoCopertura['stato'] === 'extra',
                            'outline outline-2 outline-offset-2 outline-sky-500' => $giornoCopertura['giorno']->isToday(),
                        ])
                    >
                        <div aria-hidden="true">
                            <div class="flex items-baseline justify-between gap-1">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-600">{{ ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'][$giornoCopertura['giorno']->dayOfWeekIso - 1] }}</span>
                                <span class="text-[11px] font-medium text-slate-500">{{ $giornoCopertura['giorno']->format('d/m') }}</span>
                            </div>
                            <p class="mt-1 text-sm font-semibold leading-tight text-slate-800">{{ $giornoCopertura['testo'] }}</p>
                            @if ($giornoCopertura['esito'] !== '')
                                <p @class([
                                    'mt-0.5 text-[11px] font-medium',
                                    'text-emerald-700' => $giornoCopertura['stato'] === 'ok',
                                    'text-amber-800' => $giornoCopertura['stato'] === 'scoperto',
                                    'text-sky-800' => $giornoCopertura['stato'] === 'extra',
                                ])>{{ $giornoCopertura['esito'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($vista === 'calendario')
        <div class="grid grid-cols-2 items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 p-2 shadow-sm sm:gap-3 sm:p-3 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
            <div class="col-start-1 row-start-1 inline-flex w-fit shrink-0 rounded-xl bg-white p-1 ring-1 ring-sky-200">
                @foreach (['mese' => 'Mese', 'settimana' => 'Settimana', 'giorno' => 'Giorno'] as $periodo => $etichetta)
                    <button wire:click="cambiaPeriodoCalendario('{{ $periodo }}')" class="rounded-lg px-1.5 py-1 text-[11px] font-semibold sm:px-3 sm:py-1.5 sm:text-sm {{ $periodoCalendario === $periodo ? 'bg-sky-500 text-white' : 'text-slate-600' }}">{{ $etichetta }}</button>
                @endforeach
            </div>
            <div class="col-span-2 row-start-2 flex min-w-0 items-center justify-center gap-0.5 sm:gap-2 md:col-span-1 md:col-start-2 md:row-start-1">
                <button wire:click="cambiaPeriodo(-1)" class="btn-soft !px-2 sm:!px-3" aria-label="Periodo precedente">←</button>
                <span class="min-w-0 flex-1 text-center text-xs font-bold capitalize leading-tight text-slate-800 sm:min-w-48 sm:flex-none sm:text-base">
                    @if ($periodoCalendario === 'mese')
                        {{ $calendario['primo']->locale('it')->translatedFormat('F Y') }}
                    @elseif ($periodoCalendario === 'settimana')
                        {{ $calendario['da']->locale('it')->translatedFormat('d M') }} – {{ $calendario['a']->locale('it')->translatedFormat('d M Y') }}
                    @else
                        {{ $calendario['da']->locale('it')->translatedFormat('l d F Y') }}
                    @endif
                </span>
                <button wire:click="cambiaPeriodo(1)" class="btn-soft !px-2 sm:!px-3" aria-label="Periodo successivo">→</button>
            </div>
            <div class="col-start-2 row-start-1 flex items-center justify-self-end gap-1 md:col-start-3">
                <button wire:click="oggi" class="btn-soft !px-2 text-xs sm:!px-3 sm:text-sm">Oggi</button>
            </div>
        </div>
        <div class="card overflow-hidden !ring-sky-200">
            <div class="grid {{ $periodoCalendario === 'giorno' ? 'grid-cols-1' : 'grid-cols-7' }} border-b border-sky-200 bg-sky-100/70 text-center">
                @if ($periodoCalendario === 'giorno')
                    <div class="py-2 text-xs font-semibold uppercase text-slate-500">{{ $calendario['da']->locale('it')->translatedFormat('l d/m') }}</div>
                @else
                    @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $g)<div class="py-2 text-xs font-semibold uppercase text-slate-500">{{ $g }}</div>@endforeach
                @endif
            </div>
            @php($dateCopertura = array_map(fn (array $giornoCopertura): string => $giornoCopertura['giorno']->toDateString(), $copertura))
            <div class="grid {{ $periodoCalendario === 'giorno' ? 'grid-cols-1' : 'grid-cols-7' }}">
                @for ($d = $calendario['da']->copy(); $d->lte($calendario['a']); $d->addDay())
                    @php($k = $d->toDateString())
                    @php($giorno = $calendario['turni']->get($k, collect()))
                    @php($inCopertura = $periodoCalendario === 'mese' && in_array($k, $dateCopertura, true))
                    <div wire:key="g{{ $k }}" class="group min-h-24 border-b border-r border-sky-100 p-1 sm:min-h-32 sm:p-1.5 {{ $periodoCalendario === 'giorno' ? 'min-h-48 sm:min-h-64' : '' }} {{ $inCopertura ? 'bg-sky-50/80' : ($periodoCalendario === 'mese' && $d->month !== $calendario['primo']->month ? 'bg-slate-50/60' : '') }}">
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
                <x-ui.field label="Cliente" name="f.cliente_id">
                    <div wire:key="crea-turno-cliente">
                        <x-select-ricerca
                            model="f.cliente_id"
                            :options="$clienti->map(fn ($c) => ['id' => $c->id, 'label' => $c->ragione_sociale])->values()"
                            placeholder="Seleziona un cliente"
                        />
                    </div>
                </x-ui.field>
                <x-ui.field label="Sede" name="f.sede_id">
                    <div wire:key="crea-turno-sedi-{{ $f['cliente_id'] }}">
                        <x-select-ricerca
                            model="f.sede_id"
                            :options="$sediForm->map(fn ($s) => ['id' => $s->id, 'label' => $s->descrizione])->values()"
                            placeholder="Seleziona una sede"
                        />
                    </div>
                </x-ui.field>
                <x-ui.field label="Data inizio" name="f.data_inizio"><input type="date" wire:model.live="f.data_inizio" class="input"></x-ui.field>
                <x-ui.field label="Ora inizio" name="f.ora_inizio"><input type="time" wire:model.live="f.ora_inizio" class="input"></x-ui.field>
                <x-ui.field label="Data fine" name="f.data_fine"><input type="date" wire:model.live="f.data_fine" class="input"></x-ui.field>
                <x-ui.field label="Ora fine" name="f.ora_fine"><input type="time" wire:model.live="f.ora_fine" class="input"></x-ui.field>
                <x-ui.field label="Operatore (solo disponibili)" name="f.operatore_id" class="sm:col-span-2">
                    <div wire:key="crea-turno-operatori-{{ $f['cliente_id'] }}-{{ $f['data_inizio'] }}-{{ $f['ora_inizio'] }}-{{ $f['data_fine'] }}-{{ $f['ora_fine'] }}">
                        <x-select-ricerca
                            model="f.operatore_id"
                            :options="$disponibili->map(fn ($o) => ['id' => $o->id, 'label' => ($o->preferito ? '★ ' : '').$o->nome_completo])->values()"
                            placeholder="Seleziona un operatore"
                        />
                    </div>
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

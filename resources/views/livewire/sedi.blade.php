<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="cerca" placeholder="Cerca sede o città…" class="input sm:max-w-xs">
            @unless (auth()->user()->isCliente())
                <x-ui.select-search model="filtroCliente" :live="true" placeholder="Tutti i clienti" :options="$clienti->map(fn ($c) => ['id' => (string) $c->id, 'nome' => $c->ragione_sociale])->values()" class="sm:w-72" />
            @endunless
        </div>
        @if (auth()->user()->isStaff()) <a href="{{ route('sedi.crea', array_filter(['cliente' => $filtroCliente])) }}" wire:navigate class="btn-primary">+ Nuova sede</a> @endif
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @forelse ($sedi as $s)
            <div wire:key="s{{ $s->id }}" class="card flex flex-col p-4 sm:p-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100 text-sky-600 ring-2 ring-white shadow sm:h-16 sm:w-16">
                        @if ($s->foto)
                            <img src="{{ route('sedi.foto', $s->id) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4M9 10h.01M12 10h.01M15 10h.01M9 13h.01M12 13h.01M15 13h.01"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold uppercase tracking-wide text-sky-600">{{ $s->cliente->ragione_sociale }}</p>
                        <h3 class="truncate text-lg font-bold text-slate-800">{{ $s->descrizione }}</h3>
                        <p class="truncate text-sm text-slate-500">{{ trim($s->indirizzo.' '.$s->citta) ?: '—' }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-700">{{ $s->postazioni_count }} postazioni</span>
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-700">raggio {{ $s->distanza_max }} m</span>
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-700">{{ collect($s->ore_richieste)->sum() }} h/sett.</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button wire:click="postazioni({{ $s->id }})" class="btn-soft !px-3 !py-1.5">QR e postazioni</button>
                    @if (auth()->user()->isStaff())
                        <button wire:click="qr({{ $s->id }})" class="btn-soft !px-3 !py-1.5">Genera QR</button>
                        <a href="{{ route('sedi.modifica', $s) }}" wire:navigate class="btn-soft !px-3 !py-1.5">Modifica</a>
                        <button wire:click="elimina({{ $s->id }})" wire:confirm="Eliminare la sede?" class="btn-danger !px-3 !py-1.5">Elimina</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="card col-span-full p-10 text-center text-sm text-slate-500">Nessuna sede trovata.</p>
        @endforelse
    </div>
    {{ $sedi->links() }}

    @if ($modalPostazioni && $sedeCorrente)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center sm:p-4">
            <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-2xl sm:rounded-3xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">{{ $sedeCorrente->descrizione }} · codici QR</h3>
                    <button wire:click="$set('modalPostazioni', false)" class="rounded-lg p-1 text-slate-400 hover:bg-sky-50">✕</button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-sky-50 p-4 text-center">
                        <p class="mb-2 text-sm font-semibold text-slate-700">Sede (inizio/fine turno)</p>
                        @if ($sedeCorrente->codice)<div class="mx-auto w-40">{!! $sedeCorrente->codice->svg(160) !!}</div><p class="mt-1 font-mono text-xs text-slate-500">{{ $sedeCorrente->codice->codice }}</p>@endif
                    </div>
                    @foreach ($sedeCorrente->postazioni as $post)
                        <div wire:key="p{{ $post->id }}" class="rounded-2xl bg-sky-50 p-4 text-center">
                            <p class="mb-2 text-sm font-semibold text-slate-700">{{ $post->titolo }} @if ($post->minuti_passaggio)<span class="text-xs font-normal text-slate-500">· ogni {{ $post->minuti_passaggio }} min</span>@endif</p>
                            @if ($post->codice)<div class="mx-auto w-40">{!! $post->codice->svg(160) !!}</div><p class="mt-1 font-mono text-xs text-slate-500">{{ $post->codice->codice }}</p>@endif
                            @if (auth()->user()->isStaff())<button wire:click="eliminaPostazione({{ $post->id }})" wire:confirm="Eliminare la postazione?" class="btn-danger mt-2 !px-3 !py-1">Elimina</button>@endif
                        </div>
                    @endforeach
                </div>
                @if (auth()->user()->isStaff())
                    <form wire:submit="aggiungiPostazione" class="mt-5 grid gap-3 border-t border-sky-100 pt-4 sm:grid-cols-3">
                        <x-ui.field label="Nuova postazione" name="p.titolo" class="sm:col-span-2"><input wire:model="p.titolo" class="input"></x-ui.field>
                        <x-ui.field label="Ogni (min)" name="p.minuti_passaggio"><input wire:model="p.minuti_passaggio" class="input" inputmode="numeric"></x-ui.field>
                        <button class="btn-primary sm:col-span-3">Aggiungi postazione</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($sedeQr)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center sm:p-4" @keydown.escape.window="$wire.chiudiQr()">
            <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-3xl sm:rounded-3xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Genera QR sede</h3>
                    <button type="button" wire:click="chiudiQr" class="rounded-lg p-1 text-slate-400 hover:bg-sky-50 hover:text-slate-600">✕</button>
                </div>
                <livewire:generatore-qr :sede="$sedeQr" :key="'gen'.$sedeQr" />
            </div>
        </div>
    @endif
</div>

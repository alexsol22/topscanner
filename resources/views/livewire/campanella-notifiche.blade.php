<div wire:poll.60s class="relative shrink-0" x-data="{ aperta: false }" @click.outside="aperta = false" @keydown.escape.window="aperta = false">
    <button type="button" @click="aperta = !aperta" :aria-expanded="aperta" aria-haspopup="true" aria-label="Notifiche{{ $totale > 0 ? ' ('.$totale.' da leggere)' : '' }}"
            class="relative rounded-lg p-2 text-slate-600 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-300">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        @if ($totale > 0)
            <span class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">{{ $totale > 99 ? '99+' : $totale }}</span>
        @endif
    </button>

    <div x-cloak x-show="aperta" x-transition.opacity class="fixed inset-x-4 top-16 z-50 mt-2 overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-sky-100 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:w-[28rem] sm:max-w-[calc(100vw-2rem)]">
        <div class="flex items-center justify-between border-b border-sky-50 px-4 py-3">
            <p class="text-sm font-semibold text-slate-800">Notifiche</p>
            @if ($avvisi->isNotEmpty())
                <button type="button" wire:click="segnaTuttiLetti" class="text-xs font-semibold text-sky-600 hover:underline">Segna tutte come lette</button>
            @endif
        </div>

        <div class="max-h-96 divide-y divide-sky-50 overflow-y-auto">
            @foreach ($avvisi as $vista)
                <div wire:key="avviso-{{ $vista->id }}" class="px-4 py-3">
                    <p class="text-sm font-semibold text-slate-800">{{ $vista->bacheca->titolo }}</p>
                    <p class="mt-0.5 line-clamp-3 whitespace-pre-line text-sm text-slate-600">{{ $vista->bacheca->testo }}</p>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                        <span>{{ $vista->bacheca->data_invio?->format('d/m/Y') }}</span>
                        <button type="button" wire:click="segnaLetto({{ $vista->id }})" class="font-semibold text-sky-600 hover:underline">Segna come letto</button>
                    </div>
                </div>
            @endforeach

            @foreach ($documenti as $vista)
                <a wire:key="documento-{{ $vista->id }}" href="{{ route('documenti.scarica', ['documento' => $vista->documento->id, 'visualizza' => 1]) }}" target="_blank" rel="noopener" class="block px-4 py-3 hover:bg-sky-50/60">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nuovo documento</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $vista->documento->descrizione }}</p>
                </a>
            @endforeach

            @if ($avvisi->isEmpty() && $documenti->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-slate-500">Nessuna notifica da leggere.</p>
            @endif
        </div>

        <div class="flex justify-between border-t border-sky-50 px-4 py-2.5 text-xs font-semibold text-sky-600">
            <a href="{{ route('bacheca') }}" wire:navigate @click="aperta = false" class="hover:underline">Vai alla bacheca</a>
            <a href="{{ route('documenti') }}" wire:navigate @click="aperta = false" class="hover:underline">Vai ai documenti</a>
        </div>
    </div>
</div>

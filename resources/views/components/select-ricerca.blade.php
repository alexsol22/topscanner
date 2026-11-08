@props(['model', 'options', 'placeholder' => 'Tutti', 'draft' => false, 'defer' => false])

<div
    x-data="{
        open: false,
        q: '',
        opzioni: @js($options),
        get valore() { return {{ $draft ? "this.valori['{$model}']" : "\$wire['{$model}']" }} },
        get etichetta() { return this.opzioni.find(o => String(o.id) === String(this.valore))?.label ?? '{{ $placeholder }}' },
        get filtrate() { return this.opzioni.filter(o => o.label.toLowerCase().includes(this.q.toLowerCase())) },
        scegli(id) { {{ $draft ? "this.valori['{$model}'] = id" : "\$wire.\$set('{$model}', id, ".($defer ? 'false' : 'true').")" }}; this.open = false; this.q = '' },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="if (open) { $event.stopPropagation(); open = false; }"
    class="relative"
>
    <button type="button" x-on:click="open = !open; if (open) $nextTick(() => $refs.cerca.focus())" class="input flex items-center justify-between gap-2 text-left" aria-haspopup="listbox" x-bind:aria-expanded="open">
        <span class="truncate" x-text="etichetta"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
    </button>
    <div x-cloak x-show="open" x-transition.opacity class="absolute z-30 mt-1 w-full min-w-48 rounded-xl bg-(--surface) p-2 shadow-lg ring-1 ring-sky-100">
        <input x-ref="cerca" x-model="q" type="text" placeholder="Cerca…" class="input mb-2">
        <ul class="max-h-60 overflow-y-auto" role="listbox">
            <li><button type="button" x-on:click="scegli(null)" class="w-full rounded-lg px-3 py-1.5 text-left text-sm text-slate-500 hover:bg-sky-50">{{ $placeholder }}</button></li>
            <template x-for="o in filtrate" x-bind:key="o.id">
                <li><button type="button" x-on:click="scegli(o.id)" x-text="o.label" x-bind:class="String(o.id) === String(valore) ? 'bg-sky-50 font-semibold text-sky-700' : 'text-slate-700'" class="w-full rounded-lg px-3 py-1.5 text-left text-sm hover:bg-sky-50"></button></li>
            </template>
            <li x-show="filtrate.length === 0" class="px-3 py-2 text-sm text-slate-400">Nessun risultato</li>
        </ul>
    </div>
</div>

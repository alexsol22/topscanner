@props(['model', 'options', 'placeholder' => 'Tutti', 'draft' => false, 'defer' => false, 'emptyValue' => null, 'disabled' => false])

<div
    x-data="{
        open: false,
        q: '',
        opzioni: @js($options),
        valoreVuoto: @js($emptyValue),
        get valore() { return {{ $draft ? "this.valori['{$model}']" : "\$wire['{$model}']" }} },
        get etichetta() { return this.opzioni.find(o => String(o.id) === String(this.valore))?.label ?? '{{ $placeholder }}' },
        get filtrate() { return this.opzioni.filter(o => o.label.toLowerCase().includes(this.q.toLowerCase())) },
        stileMenu: {},
        altezzaLista: '15rem',
        apri() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.aggiornaPosizione();
                    this.$refs.cerca.focus();
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
        scegli(id) { id = id ?? this.valoreVuoto; {{ $draft ? "this.valori['{$model}'] = id" : "\$wire.\$set('{$model}', id, ".($defer ? 'false' : 'true').")" }}; this.open = false; this.q = '' },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="if (open) { $event.stopPropagation(); open = false; }"
    x-on:scroll.window.capture="if (open) aggiornaPosizione()"
    x-on:resize.window="if (open) aggiornaPosizione()"
    class="relative"
>
    <button x-ref="selettore" type="button" x-on:click="apri()" @disabled($disabled) class="input flex items-center justify-between gap-2 text-left disabled:cursor-not-allowed disabled:opacity-60" aria-haspopup="listbox" x-bind:aria-expanded="open">
        <span class="truncate" x-text="etichetta"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
    </button>
    <div x-cloak x-show="open" x-transition.opacity x-bind:style="stileMenu" class="fixed z-[60] min-w-48 rounded-xl bg-(--surface) p-2 shadow-lg ring-1 ring-sky-100">
        <input x-ref="cerca" x-model="q" type="text" placeholder="Cerca…" class="input mb-2">
        <ul x-bind:style="`max-height: ${altezzaLista}`" class="overflow-y-auto" role="listbox">
            <li><button type="button" x-on:click="scegli(null)" class="w-full rounded-lg px-3 py-1.5 text-left text-sm text-slate-500 hover:bg-sky-50">{{ $placeholder }}</button></li>
            <template x-for="o in filtrate" x-bind:key="o.id">
                <li><button type="button" x-on:click="scegli(o.id)" x-text="o.label" x-bind:class="String(o.id) === String(valore) ? 'bg-sky-50 font-semibold text-sky-700' : 'text-slate-700'" class="w-full rounded-lg px-3 py-1.5 text-left text-sm hover:bg-sky-50"></button></li>
            </template>
            <li x-show="filtrate.length === 0" class="px-3 py-2 text-sm text-slate-400">Nessun risultato</li>
        </ul>
    </div>
</div>

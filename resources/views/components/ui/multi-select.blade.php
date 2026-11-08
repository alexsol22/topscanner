@props(['label', 'model', 'options'])

<div
    wire:ignore.self
    x-data="{
        selected: $wire.$entangle('{{ $model }}'),
        options: @js($options),
        q: '',
        open: false,
        get chosen() { return this.options.filter(o => this.selected.includes(o.id)); },
        get found() {
            const q = this.q.toLowerCase();
            return this.options.filter(o => !this.selected.includes(o.id) && o.nome.toLowerCase().includes(q));
        },
        add(id) { this.selected = [...this.selected, id]; this.q = ''; },
        remove(id) { this.selected = this.selected.filter(i => i !== id); },
    }"
    @click.outside="open = false"
    class="relative w-full"
>
    <label class="label">{{ $label }}</label>
    <div @click="$refs.cerca.focus(); open = true" class="flex min-h-11 w-full flex-wrap items-center gap-1.5 rounded-xl border border-sky-200 bg-white px-3 py-1.5 focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100">
        <template x-for="o in chosen" :key="o.id">
            <span class="inline-flex items-center gap-1 rounded-md bg-sky-100 px-2 py-1 text-sm text-sky-800">
                <span x-text="o.nome"></span>
                <button type="button" @click.stop="remove(o.id)" class="flex size-5 items-center justify-center rounded text-sky-700 hover:bg-sky-200" aria-label="Rimuovi operatore selezionato">×</button>
            </span>
        </template>
        <input x-ref="cerca" type="search" x-model="q" @focus="open = true" @input="open = true" @keydown.escape.stop="open = false" placeholder="Scegli operatori…" aria-label="{{ $label }}" class="min-w-40 flex-1 border-0 bg-transparent px-1 py-1 text-sm text-slate-700 outline-none focus:ring-0">
    </div>

    <div x-show="open" x-cloak class="absolute inset-x-0 z-20 mt-1 max-h-56 overflow-y-auto rounded-xl bg-white p-1 shadow-xl ring-1 ring-sky-100">
        <template x-for="o in found" :key="o.id">
            <button type="button" @click="add(o.id)" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-sky-50">
                <span x-text="o.nome"></span>
            </button>
        </template>
        <p x-show="found.length === 0" class="px-3 py-3 text-center text-sm text-slate-400">Nessun operatore trovato.</p>
    </div>
</div>

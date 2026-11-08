@props(['model', 'options', 'placeholder' => 'Seleziona…', 'live' => false, 'label' => null, 'draft' => false])

@php($id = 'select-search-'.str_replace('.', '-', $model))

<div
    x-data="{
        @if ($draft)
        get value() { return this.valori['{{ $model }}']; },
        set value(value) { this.valori['{{ $model }}'] = value; },
        @else
        value: $wire.$entangle('{{ $model }}'){{ $live ? '.live' : '' }},
        @endif
        options: @js($options),
        q: '',
        open: false,
        get label() { return this.options.find(o => String(o.id) === String(this.value))?.nome ?? ''; },
        get found() {
            const q = this.q.toLowerCase();
            return this.options.filter(o => o.nome.toLowerCase().includes(q));
        },
        pick(id) { this.value = id; this.open = false; this.q = ''; },
    }"
    @click.outside="open = false"
    @keydown.escape="if (open) { $event.stopPropagation(); open = false; }"
    {{ $attributes->class("relative") }}
>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}</label>
    @endif
    <button id="{{ $id }}" type="button" @click="open = !open; if (open) $nextTick(() => $refs.cerca.focus())" class="input flex items-center justify-between gap-2 text-left">
        <span x-text="label || '{{ $placeholder }}'" :class="label ? '' : 'text-slate-400'" class="truncate"></span>
        <span class="flex items-center gap-1">
            <span x-show="value" @click.stop="pick('')" class="text-xs text-slate-400 hover:text-slate-700" title="Svuota">✕</span>
            <span class="text-xs text-slate-400">▾</span>
        </span>
    </button>
    <div x-show="open" x-cloak class="absolute inset-x-0 z-30 mt-1 rounded-2xl bg-white p-2 shadow-xl ring-1 ring-sky-100">
        <input x-ref="cerca" type="search" x-model="q" placeholder="Cerca…" class="input mb-1">
        <div class="max-h-56 overflow-y-auto">
            <template x-for="o in found" :key="o.id">
                <button type="button" @click="pick(String(o.id))" :class="String(o.id) === String(value) ? 'bg-sky-100 font-semibold' : 'hover:bg-sky-50'" class="block w-full rounded-xl px-3 py-2 text-left text-sm" x-text="o.nome"></button>
            </template>
            <p x-show="found.length === 0" class="px-3 py-3 text-center text-sm text-slate-400">Nessun risultato.</p>
        </div>
    </div>
</div>

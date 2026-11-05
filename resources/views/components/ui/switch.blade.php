@props(['model', 'label', 'hint' => null])

<div x-data="{ v: $wire.$entangle('{{ $model }}'), get on() { return this.v === true || this.v === 'Y' || this.v === 1; } }" {{ $attributes->class('flex flex-wrap items-center gap-x-3 gap-y-1') }}>
    <button type="button" role="switch" :aria-checked="on" @click="v = !on"
        :class="on ? 'bg-emerald-500' : 'bg-slate-300'" class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors">
        <span :class="on ? 'translate-x-5' : 'translate-x-0.5'" class="absolute top-0.5 size-5 rounded-full bg-[#fff] shadow transition-transform"></span>
    </button>
    <span class="text-sm font-medium text-slate-700">{{ $label }}: <span x-text="on ? 'Attivo' : 'Disattivo'" :class="on ? 'text-emerald-600' : 'text-slate-500'" class="font-bold"></span></span>
</div>

@props(['model', 'label', 'hint' => null, 'live' => false, 'pannello' => false])

<div x-data="{ v: $wire.$entangle('{{ $model }}'){{ $live ? '.live' : '' }}, get on() { return this.v === true || this.v === 'Y' || this.v === 1; } }" {{ $attributes->class($pannello ? 'flex items-center justify-between gap-4 rounded-2xl bg-sky-50 px-4 py-3 ring-1 ring-sky-100' : 'flex flex-wrap items-center gap-x-3 gap-y-1') }}>
    @if ($pannello)
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
            @if ($hint)
                <p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
    @endif
    <button type="button" role="switch" :aria-checked="on" @click="v = !on" aria-label="{{ $label }}"
        :class="on ? '{{ $pannello ? 'bg-sky-500' : 'bg-emerald-500' }}' : 'bg-slate-300'" class="relative inline-flex {{ $pannello ? 'h-7 w-12' : 'h-6 w-11' }} shrink-0 rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2">
        <span :class="on ? '{{ $pannello ? 'translate-x-5.5' : 'translate-x-5' }}' : 'translate-x-0.5'" class="absolute top-0.5 {{ $pannello ? 'size-6' : 'size-5' }} rounded-full bg-[#fff] shadow transition-transform"></span>
    </button>
    @unless ($pannello)
        <span class="text-sm font-medium text-slate-700">{{ $label }}: <span x-text="on ? 'Attivo' : 'Disattivo'" :class="on ? 'text-emerald-600' : 'text-slate-500'" class="font-bold"></span></span>
    @endunless
</div>

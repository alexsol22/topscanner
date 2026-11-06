<span wire:poll.60s class="ml-auto">
    @if ($conteggio > 0)
        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $conteggio }} da leggere">{{ $conteggio > 99 ? '99+' : $conteggio }}</span>
    @endif
</span>

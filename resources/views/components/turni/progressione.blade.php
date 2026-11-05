@props(['turno'])

@php
    $ingresso = $turno->presenze->firstWhere('tipo', \App\Models\Legacy\Gda::INIZIO);
    $uscita = $turno->presenze->firstWhere('tipo', \App\Models\Legacy\Gda::FINE);
    $timbratureRegistrate = (int) (bool) $ingresso + (int) (bool) $uscita;
    $percentuale = $timbratureRegistrate * 50;
    $riepilogo = match ($timbratureRegistrate) {
        2 => 'Ingresso e uscita registrati.',
        1 => $ingresso ? 'Ingresso registrato, uscita in attesa.' : 'Uscita registrata, ingresso non rilevato.',
        default => 'Nessuna timbratura registrata.',
    };
@endphp

<section class="space-y-3 rounded-2xl bg-sky-50/70 p-4 ring-1 ring-sky-100" aria-label="Avanzamento timbrature">
    <div>
        <h3 class="text-sm font-bold text-slate-800">Timbrature del turno</h3>
        <p class="mt-1 text-xs text-slate-500">{{ $riepilogo }}</p>
    </div>
    <div class="min-w-52" role="group" aria-label="Avanzamento timbrature del turno">
        <div class="relative">
            <div
                class="absolute inset-x-10 top-3.5 z-0 h-1 rounded-full {{ $uscita ? 'bg-emerald-500' : 'bg-rose-500' }}"
                role="progressbar"
                aria-label="Timbrature registrate"
                aria-valuemin="0"
                aria-valuemax="2"
                aria-valuenow="{{ $timbratureRegistrate }}"
                aria-valuetext="{{ $timbratureRegistrate }} di 2 timbrature registrate"
            >
                <div class="h-full rounded-full bg-emerald-500 transition-[width] duration-300" style="width: {{ $percentuale }}%"></div>
            </div>
            <div class="relative z-10 flex items-start justify-between">
                @foreach (['Inizio' => $ingresso, 'Fine' => $uscita] as $etichetta => $timbratura)
                    <div class="flex w-20 flex-col items-center gap-1.5">
                        <span
                            @class([
                                'flex h-8 w-8 items-center justify-center rounded-full border shadow-sm',
                                'border-emerald-700/30 bg-emerald-500 text-white' => $timbratura,
                                'border-slate-300 bg-white text-slate-400' => ! $timbratura,
                            ])
                            aria-hidden="true"
                        >
                            @if ($etichetta === 'Inizio')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 4h7v16h-7M13 12H3m0 0 4-4m-4 4 4 4" />
                                    <circle cx="17" cy="12" r="0.75" fill="currentColor" stroke="none" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4v16h7M11 12h10m0 0-4-4m4 4-4 4" />
                                    <circle cx="7" cy="12" r="0.75" fill="currentColor" stroke="none" />
                                </svg>
                            @endif
                        </span>
                        <span class="text-center text-sm leading-tight">
                            <span class="block font-bold {{ $timbratura ? 'text-emerald-700' : 'text-slate-600' }}">{{ $etichetta }}</span>
                            <span class="block font-bold tabular-nums text-slate-600">{{ $timbratura?->rilevata_at?->format('H:i') ?? 'In attesa' }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

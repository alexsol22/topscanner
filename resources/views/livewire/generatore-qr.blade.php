@php
    $usati = 200 - $rimasti;
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    @if (session('qr'))
        <div role="status" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
            {{ session('qr') }}
        </div>
    @endif

    <div class="qr-studio">
        <aside class="card space-y-6 p-5">
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span>Oggi</span>
                    <span>ne restano {{ $rimasti }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-sky-100" role="meter" aria-label="Codici rimasti oggi" aria-valuemin="0" aria-valuemax="200" aria-valuenow="{{ $rimasti }}">
                    <div class="h-full rounded-full bg-sky-500" style="width: {{ max(0, min(100, (int) round($usati / 200 * 100))) }}%"></div>
                </div>
                <p class="text-sm text-slate-500">Puoi generarne da 1 a 200 al giorno. Oggi ne restano {{ $rimasti }}.</p>
            </div>

            <x-ui.field label="Numero di codici" name="quantita">
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="$set('quantita', {{ max(1, $quantita - 1) }})" @disabled($rimasti < 1 || $quantita <= 1) aria-label="Diminuisci" class="inline-flex size-10 items-center justify-center rounded-xl bg-sky-50 text-lg font-semibold text-sky-700 ring-1 ring-sky-100 disabled:cursor-not-allowed disabled:opacity-40">−</button>
                    <input type="number" min="1" max="{{ $rimasti }}" wire:model="quantita" @disabled($rimasti < 1) class="input w-full text-center disabled:cursor-not-allowed disabled:opacity-60">
                    <button type="button" wire:click="$set('quantita', {{ min(max($rimasti, 1), $quantita + 1) }})" @disabled($rimasti < 1 || $quantita >= $rimasti) aria-label="Aumenta" class="inline-flex size-10 items-center justify-center rounded-xl bg-sky-50 text-lg font-semibold text-sky-700 ring-1 ring-sky-100 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                </div>
            </x-ui.field>

            <x-ui.switch model="personalizza" label="Personalizza i codici" hint="Colore, titolo e logo. Spento, il QR resta nero." live pannello />

            @if ($personalizza)
                <div class="space-y-5">
                    <x-ui.field label="Colore del codice QR" name="colore">
                        <div class="flex items-center gap-3">
                            <input type="color" wire:model.live="colore" aria-label="Colore del codice QR" class="h-10 w-14 cursor-pointer rounded-lg border-0 bg-white p-1 ring-1 ring-sky-200">
                            <span class="font-mono text-sm text-slate-600">{{ strtoupper($colore) }}</span>
                        </div>
                    </x-ui.field>

                    <x-ui.field label="Titolo sotto il QR" name="titolo">
                        <input wire:model.live.debounce.300ms="titolo" maxlength="40" class="input" placeholder="Es. Portineria">
                    </x-ui.field>

                    <x-ui.field label="Logo al centro" name="logo">
                        <label class="flex cursor-pointer flex-col items-center gap-1 rounded-2xl border border-dashed border-sky-300 bg-sky-50/50 px-4 py-5 text-center hover:border-sky-400 hover:bg-sky-50">
                            <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="sr-only">
                            <span class="text-sm font-semibold text-slate-700">{{ $logo ? $logo->getClientOriginalName() : 'Scegli un logo' }}</span>
                            <span class="text-xs text-slate-500">PNG, JPG o WebP, massimo 2 MB</span>
                        </label>
                    </x-ui.field>
                    <div wire:loading wire:target="logo" class="text-sm text-sky-700">Caricamento logo…</div>
                    @if ($logo)
                        <button type="button" wire:click="$set('logo', null)" class="btn-soft !py-1.5">Rimuovi logo</button>
                    @endif
                    <p class="text-xs text-slate-500">Il QR mantiene la correzione errori alta, così resta leggibile con il logo al centro.</p>
                </div>
            @endif

            <button type="button" wire:click="generaLiberi" wire:loading.attr="disabled" wire:target="generaLiberi,logo" @disabled($rimasti < 1) class="btn-primary w-full py-3 disabled:cursor-wait">
                <span wire:loading.remove wire:target="generaLiberi">Genera</span>
                <span wire:loading wire:target="generaLiberi">Generazione…</span>
            </button>
        </aside>

        <section class="qr-tela sticky top-28 flex min-h-[34rem] items-center justify-center rounded-3xl p-6 ring-1 ring-slate-300 sm:p-10">
            <div class="flex w-full max-w-md flex-col items-center gap-4">
                <p class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm ring-1 ring-sky-100">Anteprima · {{ $prossimo }}</p>
                @if ($svg)
                    <div class="w-full drop-shadow-2xl [&_svg]:h-auto [&_svg]:w-full">{!! $svg !!}</div>
                    <p class="text-sm font-medium text-slate-700">Così verrà stampato {{ $prossimo }}.</p>
                @endif
            </div>
        </section>
    </div>

    @if ($appenaCreati !== [])
        <section class="card p-5 sm:p-6">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Appena generati</h2>
                    <p class="mt-1 text-sm text-slate-500">Scarica le PNG da stampare.</p>
                </div>
                <p class="text-sm font-semibold text-slate-500">{{ count($appenaCreati) }}</p>
            </div>
            <ul class="mt-4 grid max-h-96 grid-cols-1 gap-3 overflow-y-auto sm:grid-cols-2">
                @foreach ($appenaCreati as $codice)
                    <li wire:key="qr-nuovo-{{ $codice['id'] }}" class="flex items-center justify-between gap-3 rounded-2xl bg-sky-50/60 px-4 py-3 ring-1 ring-sky-100">
                        <span class="truncate font-mono text-sm font-semibold text-slate-800">{{ $codice['codice'] }}</span>
                        <a href="{{ route('codici.qrcode', $codice['id']) }}" class="btn-soft shrink-0 !px-3 !py-1.5">Scarica PNG</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

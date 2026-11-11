<div class="mx-auto max-w-5xl space-y-6">
    @if (session('qr'))
        <div role="status" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
            {{ session('qr') }}
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <section class="card space-y-5 p-5 sm:p-6">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Generazione</h2>
                <p class="mt-1 text-sm text-slate-500">
                    @if ($modalita === 'esistente')
                        La foto viene salvata su un codice libero che non ha ancora la foto.
                    @else
                        Il prossimo codice è <span class="font-mono font-semibold text-slate-700">{{ $prossimo }}</span>.
                        Quelli dopo proseguono di uno.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Tipo di codice">
                <button type="button" wire:click="$set('modalita', 'nuovo')" @class(['btn-primary' => $modalita === 'nuovo', 'btn-soft' => $modalita !== 'nuovo']) @if ($modalita === 'nuovo') aria-pressed="true" @else aria-pressed="false" @endif>Nuovo codice</button>
                <button type="button" wire:click="$set('modalita', 'esistente')" @class(['btn-primary' => $modalita === 'esistente', 'btn-soft' => $modalita !== 'esistente']) @if ($modalita === 'esistente') aria-pressed="true" @else aria-pressed="false" @endif>Codice libero senza foto</button>
            </div>

            @if ($modalita === 'esistente')
                <x-ui.field label="Codice libero senza foto" name="codiceEsistenteId">
                    <div wire:key="codici-senza-foto-{{ md5($codiciSenzaFoto->toJson()) }}">
                        <x-select-ricerca
                            model="codiceEsistenteId"
                            :options="$codiciSenzaFoto"
                            placeholder="Cerca un codice libero senza foto"
                            :empty-value="''"
                        />
                    </div>
                </x-ui.field>
            @endif

            <x-ui.field label="Colore del codice QR" name="colore">
                <div class="flex items-center gap-3">
                    <input type="color" wire:model.live="colore" class="h-10 w-14 cursor-pointer rounded-lg border-0 bg-white p-1 ring-1 ring-sky-200">
                    <span class="font-mono text-sm text-slate-500">{{ $colore }}</span>
                </div>
            </x-ui.field>

            <x-ui.field label="Titolo sotto il QR" name="titolo">
                <input wire:model.live.debounce.300ms="titolo" maxlength="40" class="input" placeholder="Es. Portineria">
            </x-ui.field>

            <x-ui.field label="Logo al centro (PNG/JPG/WebP, massimo 2 MB)" name="logo">
                <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:font-semibold file:text-sky-700">
            </x-ui.field>
            <div wire:loading wire:target="logo" class="text-sm text-sky-700">Caricamento logo…</div>

            @if ($logo)
                <button type="button" wire:click="$set('logo', null)" class="btn-soft !py-1.5">Rimuovi logo</button>
            @endif

            @if ($modalita === 'nuovo')
                <x-ui.field label="Quantità" name="quantita">
                    <input type="number" min="1" max="20" wire:model="quantita" class="input w-24">
                </x-ui.field>
            @endif

            <p class="text-xs text-slate-500">Il QR mantiene la correzione errori alta, così resta leggibile con il logo al centro.</p>

            <button type="button" wire:click="generaLiberi" wire:loading.attr="disabled" wire:target="generaLiberi,logo" class="btn-primary disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="generaLiberi">Genera</span>
                <span wire:loading wire:target="generaLiberi">Generazione…</span>
            </button>
        </section>

        <section class="card flex flex-col items-center justify-center gap-4 p-5 sm:p-6">
            <h2 class="self-start text-lg font-bold text-slate-800">Anteprima</h2>
            @if ($svg)
                <p class="self-start text-sm text-slate-500">Così verrà stampato {{ $anteprima }}.</p>
                <div class="w-full max-w-sm overflow-hidden rounded-2xl ring-1 ring-sky-100 [&_svg]:h-auto [&_svg]:w-full">{!! $svg !!}</div>
            @else
                <p class="self-start text-sm text-slate-500">Scegli un codice libero senza foto per vedere l'anteprima.</p>
            @endif
        </section>
    </div>

    @if ($appenaCreati !== [])
        <section class="card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-slate-800">Appena generati</h2>
            <p class="mt-1 text-sm text-slate-500">Scarica le PNG da stampare.</p>
            <ul class="mt-4 divide-y divide-sky-50">
                @foreach ($appenaCreati as $codice)
                    <li wire:key="qr-nuovo-{{ $codice['id'] }}" class="flex items-center justify-between gap-3 py-3">
                        <span class="font-mono font-semibold text-slate-800">{{ $codice['codice'] }}</span>
                        <a href="{{ route('codici.qrcode', $codice['id']) }}" class="btn-soft !px-3 !py-1.5">Scarica PNG</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card space-y-5 p-5 sm:p-6">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Codici</h2>
            <p class="mt-1 text-sm text-slate-500">Libero è un codice non assegnato. La foto è indicata a parte.</p>
        </div>

        <x-ui.field label="Codice" name="codiceId" class="max-w-xl">
            <div wire:key="elenco-codici-{{ md5($elencoCodici->toJson()) }}">
                <x-select-ricerca
                    model="codiceId"
                    :options="$elencoCodici"
                    placeholder="Cerca un codice"
                    :empty-value="''"
                />
            </div>
        </x-ui.field>

        @if ($codiceId !== '' && $pngSelezionato)
            <a href="{{ route('codici.qrcode', $codiceId) }}" class="btn-soft !px-3 !py-1.5">Scarica PNG</a>
        @endif
    </section>
</div>

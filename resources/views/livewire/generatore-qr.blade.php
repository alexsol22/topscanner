<div class="mx-auto max-w-5xl space-y-6">
    @if (session('qr'))
        <div role="status" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
            {{ session('qr') }}
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="border-b border-sky-100 p-5 sm:p-6">
            <h2 class="text-lg font-bold text-slate-800">QR code e immagini</h2>
            <p class="mt-1 text-sm text-slate-500">
                Seleziona un codice per vedere l'associazione, controllare la PNG e modificarne la grafica senza cambiare il codice.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="bg-sky-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Codice</th>
                        <th class="px-5 py-3">Associato a</th>
                        <th class="px-5 py-3">Immagine PNG</th>
                        <th class="px-5 py-3 text-right">Selezione</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sky-50">
                    @forelse ($codici as $riga)
                        @php
                            $codice = $riga['modello'];
                            $associazione = $codice->postazione
                                ? 'Postazione: '.$codice->postazione->titolo
                                : ($codice->sede ? 'Sede: '.$codice->sede->descrizione : 'Codice libero');
                        @endphp
                        <tr wire:key="codice-qr-{{ $codice->id }}" @class(['bg-sky-50/70' => $codiceId === $codice->id])>
                            <td class="whitespace-nowrap px-5 py-4 font-mono font-semibold text-slate-800">{{ $codice->codice }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $associazione }}</td>
                            <td class="px-5 py-4">
                                @if ($riga['immaginePresente'])
                                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Presente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Mancante
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" wire:click="selezionaCodice({{ $codice->id }})" class="btn-soft !px-3 !py-1.5">
                                    {{ $codiceId === $codice->id ? 'Selezionato' : 'Modifica' }}
                                </button>
                                @if ($riga['immaginePresente'])
                                    <a href="{{ route('codici.qrcode', $codice) }}" class="btn-soft !px-3 !py-1.5">Scarica PNG</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">Non ci sono ancora codici QR registrati.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($selezionato)
        @php
            $codice = $selezionato['modello'];
            $associazione = $codice->postazione
                ? 'Postazione: '.$codice->postazione->titolo
                : ($codice->sede ? 'Sede: '.$codice->sede->descrizione : 'Codice libero');
        @endphp
        <div class="grid gap-6 md:grid-cols-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Modifica immagine QR</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $codice->codice }} · {{ $associazione }}</p>
                </div>

                <x-ui.field label="Colore del codice QR" name="colore">
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="colore" class="h-10 w-14 cursor-pointer rounded-lg border-0 bg-white p-1 ring-1 ring-sky-200">
                        <span class="font-mono text-sm text-slate-500">{{ $colore }}</span>
                    </div>
                </x-ui.field>

                <x-ui.field label="Logo al centro (PNG/JPG/WebP, massimo 2 MB)" name="logo">
                    <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:font-semibold file:text-sky-700">
                </x-ui.field>
                <div wire:loading wire:target="logo" class="text-sm text-sky-700">Caricamento logo…</div>

                @if ($logo)
                    <button type="button" wire:click="$set('logo', null)" class="btn-soft !py-1.5">Rimuovi logo</button>
                @endif

                <p class="text-xs text-slate-500">Il QR mantiene il codice e la correzione errori alta. Se l'immagine esiste già, verrà sostituita solo dopo aver premuto il pulsante.</p>

                <button type="button" wire:click="salvaImmagine" wire:loading.attr="disabled" wire:target="salvaImmagine,logo" class="btn-primary disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="salvaImmagine">Genera o aggiorna PNG</span>
                    <span wire:loading wire:target="salvaImmagine">Generazione immagine…</span>
                </button>
            </section>

            <section class="card flex flex-col items-center justify-center gap-4 p-5 sm:p-6">
                <h2 class="self-start text-lg font-bold text-slate-800">Anteprima</h2>
                <div class="w-full max-w-sm overflow-hidden rounded-2xl ring-1 ring-sky-100 [&_svg]:h-auto [&_svg]:w-full">{!! $svg !!}</div>
            </section>
        </div>
    @else
        <p class="rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-800">Seleziona un codice dalla lista per modificarne colore e logo.</p>
    @endif
</div>

<div class="space-y-5">
    <p class="text-sm text-slate-500">
        Messaggi automatici per avvisi turno, ritardi, scadenze, riconsegna materiale e programmazione.
        L’invio è solo email. Nel testo inserisci le variabili; l’icona in elenco mostra il layout dell’email.
    </p>

    <section aria-label="Messaggi automatici" class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sky-100">
                <thead class="bg-sky-50/60">
                    <tr>
                        <th class="th">Messaggio</th>
                        <th class="th">Oggetto</th>
                        <th class="th">Tempi</th>
                        <th class="th">Canali</th>
                        <th class="th">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sky-50">
                    @forelse ($modelli as $modello)
                        <tr wire:key="modello-{{ $modello->id }}" class="align-top">
                            <td class="td font-medium text-slate-800">{{ $modello->nome }}</td>
                            <td class="td text-slate-600">{{ $modello->oggetto }}</td>
                            <td class="td whitespace-nowrap text-slate-600">{{ $modello->giorni }} g · {{ $modello->ore ?? 0 }} h · {{ $modello->minuti ?? 0 }} min</td>
                            <td class="td whitespace-nowrap text-slate-600">{{ $modello->invia_email ? 'Email' : 'Disattivato' }}</td>
                            <td class="td whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="visualizza({{ $modello->id }})" class="btn-soft !px-2.5 !py-1.5" aria-label="Vedi layout">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button type="button" wire:click="modifica({{ $modello->id }})" class="btn-soft !px-3 !py-1.5">Modifica</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="td py-10 text-center text-slate-500">Nessun modello automatico disponibile.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($modal)
        <x-ui.modal title="Modifica messaggio" wide close="chiudi">
            <form wire:submit="salva" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="modello-nome" class="label">Nome</label>
                    <input id="modello-nome" wire:model="f.nome" class="input">
                    @error('f.nome') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="modello-giorni" class="label">Giorni</label>
                    <input id="modello-giorni" type="number" wire:model="f.giorni" class="input">
                    @error('f.giorni') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="modello-ore" class="label">Ore</label>
                    <input id="modello-ore" type="number" wire:model="f.ore" class="input">
                    @error('f.ore') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="modello-minuti" class="label">Minuti</label>
                    <input id="modello-minuti" type="number" wire:model="f.minuti" class="input">
                    @error('f.minuti') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="f.invia_email" class="rounded border-sky-300 text-sky-500"> Invia email</label>
                <div class="sm:col-span-2">
                    <label for="modello-oggetto" class="label">Oggetto email</label>
                    <input id="modello-oggetto" wire:model="f.oggetto" class="input">
                    @error('f.oggetto') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="modello-email" class="label">Testo email</label>
                    <div class="mb-2 flex flex-wrap gap-1.5">
                        @foreach (\App\Livewire\ModelliMessaggio::VARIABILI as $variabile)
                            <button
                                type="button"
                                data-variabile="{{ $variabile }}"
                                class="rounded-lg bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100"
                                x-on:click="const el = document.getElementById('modello-email'); const v = $el.dataset.variabile; const inizio = el.selectionStart ?? el.value.length; const fine = el.selectionEnd ?? el.value.length; el.value = el.value.slice(0, inizio) + v + el.value.slice(fine); el.selectionStart = el.selectionEnd = inizio + v.length; el.dispatchEvent(new Event('input', { bubbles: true })); el.focus()"
                            >{{ $variabile }}</button>
                        @endforeach
                    </div>
                    <textarea id="modello-email" wire:model="f.modello_email" rows="4" class="input"></textarea>
                    @error('f.modello_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="modello-admin-email" class="label">Email amministrazione (separate da virgola)</label>
                    <input id="modello-admin-email" wire:model="f.admin_email" class="input">
                    @error('f.admin_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="modello-numero-email" class="label">Numero invii email</label>
                    <select id="modello-numero-email" wire:model="f.numero_email" class="input">@foreach ([1, 2, 3] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach</select>
                    @error('f.numero_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" wire:click="chiudi" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Salva</button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($anteprima)
        <x-ui.modal title="Layout email" wide="xl" close="chiudiAnteprima">
            <iframe
                title="Layout dell'email"
                sandbox=""
                referrerpolicy="no-referrer"
                class="h-[70vh] w-full rounded-xl border border-sky-100 bg-[#fff]"
                srcdoc="{{ $anteprimaEmail }}"
            ></iframe>
            <div class="mt-4 flex justify-end">
                <button type="button" wire:click="chiudiAnteprima" class="btn-soft">Chiudi</button>
            </div>
        </x-ui.modal>
    @endif
</div>

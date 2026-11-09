<div class="space-y-5">
    <p class="text-sm text-slate-500">
        Messaggi automatici per avvisi turno, ritardi, scadenze e riconsegna materiale.
        Segnaposto: <code class="rounded bg-sky-50 px-1">[OPERATORE] [SEDE] [DATA TURNO] [ORA TURNO] [INDIRIZZO]</code>
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
                            <td class="td whitespace-nowrap text-slate-600">{{ $modello->invia_email ? 'Email' : '' }}{{ $modello->invia_email && $modello->invia_sms ? ' + ' : '' }}{{ $modello->invia_sms ? 'SMS' : '' }}{{ ! $modello->invia_email && ! $modello->invia_sms ? 'Disattivato' : '' }}</td>
                            <td class="td whitespace-nowrap">
                                <button type="button" wire:click="modifica({{ $modello->id }})" class="btn-soft !px-3 !py-1.5">Modifica</button>
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
        <x-ui.modal title="Modifica messaggio" wide>
            <form wire:submit="salva" class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3">
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
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="f.invia_sms" class="rounded border-sky-300 text-sky-500"> Invia SMS</label>
                <div>
                    <label for="modello-mittente" class="label">Mittente SMS</label>
                    <input id="modello-mittente" wire:model="f.mittente" class="input">
                    @error('f.mittente') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="modello-oggetto" class="label">Oggetto email</label>
                    <input id="modello-oggetto" wire:model="f.oggetto" class="input">
                    @error('f.oggetto') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="modello-email" class="label">Testo email</label>
                    <textarea id="modello-email" wire:model="f.modello_email" rows="4" class="input"></textarea>
                    @error('f.modello_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="modello-sms" class="label">Testo SMS</label>
                    <textarea id="modello-sms" wire:model="f.modello_sms" rows="2" class="input"></textarea>
                    @error('f.modello_sms') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="modello-admin-email" class="label">Email amministrazione (separate da virgola)</label>
                    <input id="modello-admin-email" wire:model="f.admin_email" class="input">
                    @error('f.admin_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="modello-numero-sms" class="label">Numero invii SMS</label>
                    <select id="modello-numero-sms" wire:model="f.numero_sms" class="input">@foreach ([1, 2, 3] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach</select>
                    @error('f.numero_sms') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="modello-numero-email" class="label">Numero invii email</label>
                    <select id="modello-numero-email" wire:model="f.numero_email" class="input">@foreach ([1, 2, 3] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach</select>
                    @error('f.numero_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="modello-admin-sms" class="label">Numeri SMS amministrazione</label>
                    <input id="modello-admin-sms" wire:model="f.admin_sms" class="input">
                    @error('f.admin_sms') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2 sm:col-span-3">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Salva</button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>

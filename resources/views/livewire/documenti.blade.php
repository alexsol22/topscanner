<div class="space-y-5">
    @if (auth()->user()->isStaff())
        <x-ui.filtri :values="['filtroCliente' => $filtroCliente, 'filtroOperatore' => $filtroOperatore, 'filtroTipologia' => $filtroTipologia, 'filtroDescrizione' => $filtroDescrizione]">
           <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="label">Cliente</label>
                <x-ui.select-search model="filtroCliente" :draft="true" placeholder="Scegli" :options="$clienti->map(fn ($c) => ['id' => (string) $c->id, 'nome' => $c->ragione_sociale])->values()" />
            </div>
            <div>
                <label class="label">Operatore</label>
                <x-ui.select-search model="filtroOperatore" :draft="true" placeholder="Scegli" :options="$operatori->map(fn ($o) => ['id' => (string) $o->id, 'nome' => $o->nome_completo])->values()" />
            </div>
            <div>
                <label class="label">Tipologia</label>
                <x-ui.select-search model="filtroTipologia" :draft="true" placeholder="Scegli" :options="$tipologie->map(fn ($t) => ['id' => (string) $t->id, 'nome' => $t->nome])->values()" />
            </div>
            <div>
                <label for="filtro-descrizione" class="label">Descrizione</label>
                <input id="filtro-descrizione" type="search" x-model="valori.filtroDescrizione" placeholder="Cerca descrizione..." class="input">
            </div>
           </div>
        </x-ui.filtri>
    @endif

    <section aria-label="Elenco documenti" class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sky-100">
                <thead class="bg-sky-50/60">
                    <tr>
                        <th class="th">Cliente</th>
                        <th class="th">Operatore</th>
                        <th class="th">Tipologia</th>
                        <th class="th">Descrizione</th>
                        <th class="th">Scadenza</th>
                        <th class="th">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sky-50">
                    @forelse ($documenti as $d)
                        <tr wire:key="d{{ $d->id }}" class="hover:bg-sky-50/40">
                            <td class="td">{{ $d->tipo === 'C' ? $d->cliente?->ragione_sociale : '—' }}</td>
                            <td class="td">{{ $d->tipo === 'O' ? $d->operatore?->nome_completo : '—' }}</td>
                            <td class="td">{{ $d->certificato?->nome ?? '—' }}</td>
                            <td class="td max-w-sm whitespace-normal font-medium text-slate-800">{{ $d->descrizione }}</td>
                            <td class="td whitespace-nowrap">
                                @if ($d->scadenza)
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $d->scadenza->isPast() ? 'bg-rose-100 text-rose-700' : ($d->inScadenza() ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $d->scadenza->format('d/m/Y') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="td whitespace-nowrap">
                                <a href="{{ route('documenti.scarica', $d) }}" class="btn-soft !px-3 !py-1.5">Scarica</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="td py-10 text-center text-slate-500">Nessun documento disponibile.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $documenti->links() }}

    @if ($modal)
        <x-ui.modal title="Carica documento">
            <form wire:submit="salva" class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Titolo" name="f.titolo" class="sm:col-span-2"><input wire:model="f.titolo" class="input"></x-ui.field>
                <x-ui.field label="Tipo" name="f.tipo"><select wire:model.live="f.tipo" class="input"><option value="cliente">Cliente</option><option value="operatore">Operatore</option></select></x-ui.field>
                @if ($f['tipo'] === 'cliente')
                    <x-ui.field label="Cliente" name="f.cliente_id"><select wire:model="f.cliente_id" class="input"><option value="">—</option>@foreach ($clienti as $c)<option value="{{ $c->id }}">{{ $c->ragione_sociale }}</option>@endforeach</select></x-ui.field>
                @else
                    <x-ui.field label="Operatore" name="f.user_id"><select wire:model="f.user_id" class="input"><option value="">—</option>@foreach ($operatori as $o)<option value="{{ $o->id }}">{{ $o->nome_completo }}</option>@endforeach</select></x-ui.field>
                @endif
                <x-ui.field label="Tipologia" name="f.tipologia"><select wire:model="f.tipologia" class="input"><option value="">—</option>@foreach ($tipologie as $t)<option value="{{ $t->id }}">{{ $t->nome }}</option>@endforeach</select></x-ui.field>
                <x-ui.field label="Scadenza" name="f.scadenza"><input type="date" wire:model="f.scadenza" class="input"></x-ui.field>
                <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-600"><input type="checkbox" wire:model="f.formazione" class="rounded border-sky-300 text-sky-500"> Formazione</label>
                <x-ui.field label="File (max 10 MB)" name="file" class="sm:col-span-2"><input type="file" wire:model="file" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:font-semibold file:text-sky-700"></x-ui.field>
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Annulla</button>
                    <button class="btn-primary" wire:loading.attr="disabled" wire:target="file">Salva</button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>

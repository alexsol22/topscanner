<div class="mx-auto max-w-3xl space-y-5">
    @if (auth()->user()->isStaff())
        <div class="flex justify-end"><button wire:click="nuovo" class="btn-primary">+ Nuovo avviso</button></div>
    @endif

    @forelse ($avvisi as $a)
        <article wire:key="a{{ $a->id }}" class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">{{ $a->titolo }}</h3>
                    <p class="text-xs text-slate-500">{{ $a->data_invio?->translatedFormat('d F Y') }}</p>
                </div>
                @if (auth()->user()->isOperatore())
                    @if (! $a->letto)<span class="rounded-full bg-sky-500 px-2.5 py-1 text-xs font-semibold text-white">Nuovo</span>@endif
                @else
                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $a->letti_count }}/{{ $a->visualizzazioni_count }} letti</span>
                @endif
            </div>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $a->testo }}</p>
            <div class="mt-4 flex gap-2">
                @if (auth()->user()->isOperatore() && ! $a->letto)<button wire:click="segnaLetto({{ $a->id }})" class="btn-soft !px-3 !py-1.5">Segna come letto</button>@endif
                @if (auth()->user()->isStaff())
                    <button wire:click="mostraVisti({{ $a->id }})" class="btn-soft !px-3 !py-1.5">Visti</button>
                    <button wire:click="modifica({{ $a->id }})" class="btn-soft !px-3 !py-1.5">Modifica</button>
                    <button wire:click="elimina({{ $a->id }})" wire:confirm="Eliminare l'avviso?" class="btn-danger !px-3 !py-1.5">Elimina</button>
                @endif
            </div>
        </article>
    @empty
        <p class="card p-10 text-center text-sm text-slate-500">Nessun avviso in bacheca.</p>
    @endforelse

    @if ($modal)
        <x-ui.modal :title="$modificaId ? 'Modifica avviso' : 'Nuovo avviso'">
            <form wire:submit="salva" class="space-y-4">
                <x-ui.field label="Titolo" name="f.titolo"><input wire:model="f.titolo" class="input"></x-ui.field>
                <x-ui.field label="Testo" name="f.testo"><textarea wire:model="f.testo" rows="5" class="input"></textarea></x-ui.field>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model.live="f.per_tutti" class="rounded border-sky-300 text-sky-500"> Invia a tutti gli operatori</label>
                @unless ($f['per_tutti'])
                    <div class="max-h-40 space-y-1 overflow-y-auto rounded-xl bg-sky-50 p-3">
                        @foreach ($operatori as $o)<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="destinatari" value="{{ $o->id }}" class="rounded border-sky-300 text-sky-500"> {{ $o->nome_completo }}</label>@endforeach
                    </div>
                    @error('destinatari') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                @endunless
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Annulla</button>
                    <button class="btn-primary">{{ $modificaId ? 'Salva' : 'Pubblica' }}</button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($vistiId)
        <x-ui.modal title="Visualizzazioni" close="chiudiVisti">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-sky-50">
                <thead class="bg-sky-50/60"><tr><th class="th">Operatore</th><th class="th">Visto</th></tr></thead>
                <tbody class="divide-y divide-sky-50">
                    @forelse ($vistiElenco as $v)
                        <tr><td class="td">{{ $v->operatore?->codice }} ({{ $v->operatore?->cognome }} {{ $v->operatore?->nome }})</td><td class="td">{{ $v->visto ? 'Visto il '.$v->data_vista?->format('d-m-Y H:i') : '' }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="td text-center text-slate-500">Nessun destinatario.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="mt-5 flex justify-end"><button type="button" wire:click="chiudiVisti" class="btn-soft">Chiudi</button></div>
        </x-ui.modal>
    @endif
</div>

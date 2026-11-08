<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-ui.filtri :values="['cerca' => $cerca]">
            <label for="cerca-amministratori" class="label">Nome o email</label>
            <input id="cerca-amministratori" type="search" x-model="valori.cerca" placeholder="Cerca per nome o email…" class="input">
        </x-ui.filtri>
        <button wire:click="nuovo" class="btn-primary">+ Nuovo amministratore</button>
    </div>

    <div class="card overflow-hidden"><div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sky-100">
            <thead class="bg-sky-50/60"><tr><th class="th">Nome</th><th class="th">Email (login)</th><th class="th">Cellulare</th><th class="th">Stato</th><th class="th"></th></tr></thead>
            <tbody class="divide-y divide-sky-50">
                @forelse ($amministratori as $amministratore)
                    <tr wire:key="amministratore-{{ $amministratore->id }}" class="hover:bg-sky-50/40">
                        <td class="td font-semibold text-slate-800">{{ $amministratore->nome_completo }}</td>
                        <td class="td">{{ $amministratore->email }}</td>
                        <td class="td">{{ $amministratore->cellulare ?: '—' }}</td>
                        <td class="td"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $amministratore->attivo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $amministratore->attivo ? 'Attivo' : 'Disattivo' }}</span></td>
                        <td class="td whitespace-nowrap text-right">
                            <button wire:click="modifica({{ $amministratore->id }})" class="btn-soft !px-3 !py-1.5">Modifica</button>
                            <button wire:click="attivaDisattiva({{ $amministratore->id }})" class="btn-danger !px-3 !py-1.5">{{ $amministratore->attivo ? 'Disattiva' : 'Attiva' }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td text-center text-slate-500">Nessun amministratore trovato.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
    {{ $amministratori->links() }}

    @if ($modal)
        <x-ui.modal :title="$userId ? 'Modifica amministratore' : 'Nuovo amministratore'">
            <form wire:submit="salva" class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Nome" name="f.name"><input wire:model="f.name" class="input"></x-ui.field>
                <x-ui.field label="Cognome" name="f.cognome"><input wire:model="f.cognome" class="input"></x-ui.field>
                <x-ui.field label="Email (login)" name="f.email" class="sm:col-span-2"><input type="email" wire:model="f.email" class="input"></x-ui.field>
                <x-ui.field label="Cellulare" name="f.cellulare"><input wire:model="f.cellulare" class="input"></x-ui.field>
                <x-ui.field :label="$userId ? 'Nuova password (opzionale)' : 'Password'" name="f.password"><input type="password" wire:model="f.password" class="input" autocomplete="new-password"></x-ui.field>
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Salva</button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>

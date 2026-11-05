@props(['title', 'atteso', 'azione' => 'elimina', 'chiudi' => 'annullaEliminazione'])
<x-ui.modal :title="$title" :close="$chiudi">
    <div x-data="{ testo: '', atteso: @js($atteso), norm(v) { return (v || '').replace(/\s+/g, ' ').trim().toLowerCase() }, get ok() { return this.norm(this.testo) === this.norm(this.atteso) } }" class="space-y-4">
        <div class="flex gap-3 rounded-2xl bg-rose-50 p-4 ring-1 ring-rose-100">
            <svg class="h-6 w-6 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <p class="text-sm font-semibold text-rose-700">Operazione definitiva: non può essere annullata.</p>
        </div>

        <div class="space-y-2 text-sm text-slate-600">{{ $slot }}</div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Per confermare scrivi <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono font-bold text-slate-800">{{ $atteso }}</span></label>
            <input type="text" x-model="testo" wire:model="conferma" autocomplete="off" class="input" @keydown.enter.prevent="ok && $wire.call('{{ $azione }}')">
            @error('conferma')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" wire:click="{{ $chiudi }}" class="btn-soft">Annulla</button>
            <button type="button" wire:click="{{ $azione }}" wire:loading.attr="disabled" x-bind:disabled="! ok" class="btn-danger disabled:cursor-not-allowed disabled:opacity-40">Elimina definitivamente</button>
        </div>
    </div>
</x-ui.modal>

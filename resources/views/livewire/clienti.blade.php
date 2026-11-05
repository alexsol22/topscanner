<div class="space-y-5" x-on:scorri-al-primo-errore.window="$nextTick(() => document.querySelector('.text-rose-600')?.scrollIntoView({ behavior: 'smooth', block: 'center' }))">
    @if (session('esito'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-100">{{ session('esito') }}</div>
    @endif

    @if ($daEliminare)
        <x-ui.conferma-eliminazione :title="'Eliminare '.$daEliminare->ragione_sociale.'?'" atteso="conferma">
            <p>Verranno eliminati <strong>definitivamente</strong> il cliente e tutti i dati collegati:</p>
            <ul class="list-disc space-y-0.5 pl-5">
                <li>{{ $riepilogo['sedi'] }} sedi e {{ $riepilogo['postazioni'] }} postazioni</li>
                <li>{{ $riepilogo['turni'] }} turni con i relativi passaggi</li>
                <li>{{ $riepilogo['documenti'] }} documenti del cliente</li>
                <li>{{ $riepilogo['account'] }} account di accesso al portale (non potranno più entrare)</li>
                <li>Operatori preferiti ed esclusi</li>
                <li>Tutti i file caricati (documenti, foto di sedi e postazioni)</li>
            </ul>
            <p>Gli operatori non vengono eliminati.</p>
        </x-ui.conferma-eliminazione>
    @endif

    @if (! $modal)
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-1">
                <input type="search" wire:model.live.debounce.300ms="cerca" placeholder="Cerca cliente o città…" class="input sm:max-w-xs">
            </div>
            <button wire:click="nuovo" class="btn-primary">+ Nuovo cliente</button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-sky-100">
                    <thead class="bg-sky-50/60"><tr><th class="th">Ragione Sociale</th><th class="th">Iva / Codice Fiscale</th><th class="th">Referente</th><th class="th">Email</th><th class="th">Telefono</th><th class="th text-center">Sedi</th><th class="th text-right">Azioni</th></tr></thead>
                    <tbody class="divide-y divide-sky-50">
                        @forelse ($clienti as $c)
                            <tr wire:key="c{{ $c->id }}" class="hover:bg-sky-50/40">
                                <td class="td font-semibold text-slate-800">{{ $c->ragione_sociale }}</td>
                                <td class="td">{{ $c->piva ?: $c->codice_fiscale }}</td>
                                <td class="td">{{ trim($c->nome.' '.$c->cognome) }}</td>
                                <td class="td">{{ $c->email }}</td>
                                <td class="td">{{ $c->telefono }}</td>
                                <td class="td text-center">
                                    <a href="{{ route('sedi', ['cliente' => $c->id]) }}" wire:navigate title="Vai alle sedi di {{ $c->ragione_sociale }}" @class(['inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-semibold transition', 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => $c->sedi_count > 0, 'bg-rose-50 text-rose-600 hover:bg-rose-100' => $c->sedi_count === 0])>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $c->sedi_count }}
                                    </a>
                                </td>
                                <td class="td whitespace-nowrap text-right">
                                    <button wire:click="modifica({{ $c->id }})" class="btn-soft !px-3 !py-1.5">Modifica</button>
                                    <button wire:click="richiediEliminazione({{ $c->id }})" class="btn-danger !px-3 !py-1.5">Elimina</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="td py-10 text-center text-slate-500">Nessun cliente trovato.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $clienti->links() }}
    @endif

    @if ($modal)
        <div class="card p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-800">{{ $clienteId ? 'Modifica cliente' : 'Nuovo cliente' }}</h2>
                <button type="button" wire:click="$set('modal', false)" class="btn-soft">← Torna all'elenco</button>
            </div>
            <form wire:submit="salva">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100" role="alert">
                        <p class="font-semibold">Controlla i campi evidenziati prima di salvare:</p>
                        <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $errore)<li>{{ $errore }}</li>@endforeach</ul>
                    </div>
                @endif
                <div class="mb-4 flex gap-1 overflow-x-auto rounded-2xl bg-sky-50 p-1.5">
                    @foreach (['dati' => 'Dati '.($f['ragione_sociale'] ?? ''), 'documenti' => 'Documenti', 'operatori' => 'Gestione operatori'] as $chiave => $etichetta)
                        <button type="button" wire:click="$set('tab', '{{ $chiave }}')" @disabled($chiave === 'documenti' && ! $clienteId)
                            class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition disabled:opacity-40 {{ $tab === $chiave ? 'bg-sky-500 text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">{{ $etichetta }}</button>
                    @endforeach
                </div>

                <div @class(['grid gap-4 sm:grid-cols-2 lg:grid-cols-3', 'hidden' => $tab !== 'dati'])>
                <x-ui.switch model="f.accesso" label="Abilita accesso" hint="Al salvataggio crea l'account di accesso del cliente (utente = email) e gli invia le credenziali via email. Se l'account esiste già non cambia nulla; disattivarlo non blocca un account già creato." class="sm:col-span-2" />
                <x-ui.field label="Colore" name="f.colore"><div class="flex items-center gap-2"><input type="color" wire:model.live="f.colore" class="h-10 w-14 cursor-pointer rounded-lg border border-sky-100 bg-white p-1"><input wire:model.live.debounce.400ms="f.colore" class="input" maxlength="7"></div></x-ui.field>
                <x-ui.field label="Ragione sociale *" name="f.ragione_sociale" class="sm:col-span-2 lg:col-span-3"><input wire:model="f.ragione_sociale" class="input"></x-ui.field>
                <x-ui.field label="Nome referente" name="f.nome"><input wire:model="f.nome" class="input"></x-ui.field>
                <x-ui.field label="Cognome referente" name="f.cognome"><input wire:model="f.cognome" class="input"></x-ui.field>
                <x-ui.field label="Codice fiscale" name="f.codice_fiscale"><input wire:model="f.codice_fiscale" class="input"></x-ui.field>
                <x-ui.field label="P. IVA *" name="f.piva"><input wire:model="f.piva" class="input" maxlength="11"></x-ui.field>
                <x-ui.field label="Indirizzo" name="f.indirizzo" class="sm:col-span-2"><input wire:model="f.indirizzo" class="input"></x-ui.field>
                <x-ui.field label="CAP" name="f.cap"><input wire:model="f.cap" class="input" maxlength="5"></x-ui.field>
                <x-ui.field label="Provincia" name="f.provincia_id"><x-ui.select-search model="f.provincia_id" :options="$province->map(fn ($p) => ['id' => (string) $p->id, 'nome' => $p->sigla.' — '.$p->nome])->values()" placeholder="Scegli provincia" live /></x-ui.field>
                <x-ui.field label="Città" name="f.citta_id"><x-ui.select-search wire:key="citta-{{ $f['provincia_id'] ?? 0 }}" model="f.citta_id" :options="$comuni->map(fn ($c) => ['id' => (string) $c->id, 'nome' => $c->comune])->values()" placeholder="Scegli città" /></x-ui.field>
                <x-ui.field label="Email *" name="f.email"><input type="email" wire:model="f.email" class="input"></x-ui.field>
                <x-ui.field label="PEC" name="f.pec"><input type="email" wire:model="f.pec" class="input"></x-ui.field>
                <x-ui.field label="Cellulare" name="f.cellulare"><input wire:model="f.cellulare" class="input"></x-ui.field>
                <x-ui.field label="Telefono" name="f.telefono"><input wire:model="f.telefono" class="input"></x-ui.field>
                <x-ui.field label="Fax" name="f.fax"><input wire:model="f.fax" class="input"></x-ui.field>
                </div>

                @if ($tab === 'documenti' && $clienteId)
                    <div class="space-y-4">
                        <ul class="divide-y divide-sky-100 rounded-2xl ring-1 ring-sky-100">
                            @forelse ($documenti as $d)
                                <li wire:key="d{{ $d->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                    <a href="{{ route('documenti.scarica', $d) }}" class="truncate font-medium text-sky-700 hover:underline">{{ $d->descrizione }} <span class="text-xs text-slate-400">{{ $d->file }}</span></a>
                                    <button type="button" wire:click="eliminaDocumento({{ $d->id }})" wire:confirm="Eliminare il documento?" class="btn-danger !px-3 !py-1">Elimina</button>
                                </li>
                            @empty
                                <li class="px-4 py-6 text-center text-sm text-slate-500">Nessun documento.</li>
                            @endforelse
                        </ul>
                        <div class="grid gap-3 rounded-2xl bg-sky-50 p-4 sm:grid-cols-2">
                            <x-ui.field label="Titolo / Descrizione" name="titoloDocumento"><input wire:model="titoloDocumento" class="input"></x-ui.field>
                            <x-ui.field label="Allega file" name="fileDocumento"><input type="file" wire:model="fileDocumento" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:font-semibold file:text-sky-700"></x-ui.field>
                            <button type="button" wire:click="aggiungiDocumento" wire:loading.attr="disabled" wire:target="fileDocumento,aggiungiDocumento" class="btn-primary sm:col-span-2">Aggiungi documento</button>
                        </div>
                        <p class="text-xs text-slate-500">Formati: png, jpg, xls, xlsx, pdf, mp4, zip.</p>
                    </div>
                @endif

                @if ($tab === 'operatori')
                    @php($elencoOperatori = $operatori->map(fn ($o) => ['id' => (string) $o->id, 'nome' => trim($o->cognome.' '.$o->nome)])->values())
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-ui.multi-select label="Operatori preferiti" model="preferiti" :options="$elencoOperatori" />
                        <x-ui.multi-select label="Escludi operatori" model="escludi" :options="$elencoOperatori" tone="rose" />
                    </div>
                @endif

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Salva</button>
                </div>
            </form>
        </div>
    @endif
</div>

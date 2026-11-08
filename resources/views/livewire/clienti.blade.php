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
            <h2 class="text-lg font-bold text-slate-800">Elenco clienti</h2>
            <div class="flex items-center gap-2">
                <x-ui.filtri title="Ricerca cliente" trigger-label="Ricerca cliente" :values="['filtroRagioneSociale' => $filtroRagioneSociale, 'filtroPiva' => $filtroPiva, 'filtroCodiceFiscale' => $filtroCodiceFiscale, 'filtroEmail' => $filtroEmail, 'filtroTelefono' => $filtroTelefono]">
                    <x-slot:trigger>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg>
                    </x-slot:trigger>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label for="cliente-ragione-sociale" class="label">Ragione Sociale</label><input id="cliente-ragione-sociale" type="search" x-model="valori.filtroRagioneSociale" class="input"></div>
                        <div><label for="cliente-piva" class="label">P.iva</label><input id="cliente-piva" type="search" x-model="valori.filtroPiva" class="input"></div>
                        <div><label for="cliente-codice-fiscale" class="label">Codice Fiscale</label><input id="cliente-codice-fiscale" type="search" x-model="valori.filtroCodiceFiscale" class="input"></div>
                        <div><label for="cliente-email" class="label">Email</label><input id="cliente-email" type="search" x-model="valori.filtroEmail" class="input"></div>
                        <div><label for="cliente-telefono" class="label">Telefono</label><input id="cliente-telefono" type="search" x-model="valori.filtroTelefono" class="input" inputmode="tel"></div>
                    </div>
                </x-ui.filtri>
                <button type="button" wire:click="nuovo" title="Aggiungi cliente" aria-label="Aggiungi cliente" class="btn-primary !p-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                </button>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-sky-100">
                    <thead class="bg-sky-50/60">
                        <tr>
                            <th class="th">Ragione Sociale</th>
                            <th class="th">Iva / Codice Fiscale</th>
                            <th class="th">Referente</th>
                            <th class="th">Email</th>
                            <th class="th">Telefono</th>
                            <th class="th text-center">Operatori</th>
                            <th class="th text-center">Gestisci</th>
                            <th class="th text-center">Mod</th>
                            <th class="th text-center">Can</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sky-50">
                        @forelse ($clienti as $c)
                            <tr wire:key="c{{ $c->id }}" class="hover:bg-sky-50/40">
                                <td class="td font-semibold text-slate-800">{{ $c->ragione_sociale }}</td>
                                <td class="td">
                                    @if ($c->piva)
                                        <div>P.iva: {{ $c->piva }}</div>
                                    @endif
                                    @if ($c->codice_fiscale)
                                        <div>Codice fiscale: {{ $c->codice_fiscale }}</div>
                                    @endif
                                </td>
                                <td class="td">{{ trim($c->nome.' '.$c->cognome) }}</td>
                                <td class="td">
                                    @if ($c->email)
                                        <div>Email: {{ $c->email }}</div>
                                    @endif
                                    @if ($c->pec)
                                        <div>Pec: {{ $c->pec }}</div>
                                    @endif
                                </td>
                                <td class="td">{{ $c->telefono }}</td>
                                <td class="td text-center">
                                    @if ($c->operatori_preferiti_count + $c->operatori_esclusi_count > 0)
                                        <button type="button" wire:click="mostraOperatori({{ $c->id }})" title="Visualizza operatori di {{ $c->ragione_sociale }}" aria-label="Visualizza operatori di {{ $c->ragione_sociale }}" class="btn-soft !p-2">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m16 0v-2a4 4 0 00-3-3.87M14 3.13a4 4 0 010 7.75M10 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        </button>
                                    @endif
                                </td>
                                <td class="td text-center">
                                    <a href="{{ route('sedi', ['cliente' => $c->id]) }}" wire:navigate title="Gestisci sedi di {{ $c->ragione_sociale }}" aria-label="Gestisci sedi di {{ $c->ragione_sociale }}" class="btn-soft !p-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4M9 10h.01M12 10h.01M15 10h.01"/></svg>
                                    </a>
                                </td>
                                <td class="td text-center">
                                    <button type="button" wire:click="modifica({{ $c->id }})" title="Modifica cliente {{ $c->ragione_sociale }}" aria-label="Modifica cliente {{ $c->ragione_sociale }}" class="btn-soft !p-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l2.651 2.651M5 19l4.25-.85L19.513 7.887a1.875 1.875 0 00-2.65-2.65L6.6 15.5 5 19z"/></svg>
                                    </button>
                                </td>
                                <td class="td text-center">
                                    <button type="button" wire:click="richiediEliminazione({{ $c->id }})" title="Cancella cliente {{ $c->ragione_sociale }}" aria-label="Cancella cliente {{ $c->ragione_sociale }}" class="btn-danger !p-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-10 0V5h8v2m-9 0l1 13h8l1-13M10 11v5m4-5v5"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="td py-10 text-center text-slate-500">Nessun cliente trovato.</td></tr>
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
                    <div class="space-y-4">
                        <x-ui.multi-select label="Operatori preferiti" model="preferiti" :options="$elencoOperatori" />
                        <x-ui.multi-select label="Escludi operatori" model="escludi" :options="$elencoOperatori" />
                    </div>
                @endif

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="$set('modal', false)" class="btn-soft">Annulla</button>
                    <button class="btn-primary">Salva</button>
                </div>
            </form>
        </div>
    @endif

    @if ($clienteOperatori)
        <x-ui.modal title="Operatori cliente — {{ $clienteOperatori->ragione_sociale }}" close="chiudiOperatori">
            <div class="space-y-5">
                @foreach (['Preferiti' => [$operatoriPreferiti, 'emerald'], 'Esclusi' => [$operatoriEsclusi, 'rose']] as $gruppo => [$operatoriGruppo, $colore])
                    @if ($operatoriGruppo->isNotEmpty())
                        <section>
                            <h4 @class(['mb-2 text-sm font-bold', 'text-emerald-700' => $colore === 'emerald', 'text-rose-700' => $colore === 'rose'])>Operatori {{ $gruppo }}</h4>
                            <div class="overflow-x-auto rounded-xl ring-1 ring-sky-100">
                                <table class="min-w-full divide-y divide-sky-100 text-sm">
                                    <thead class="bg-sky-50/60"><tr><th class="px-3 py-2 text-left font-semibold">Codice</th><th class="px-3 py-2 text-left font-semibold">Operatore</th><th class="px-3 py-2 text-right font-semibold">Costo / ora</th></tr></thead>
                                    <tbody class="divide-y divide-sky-50">
                                        @foreach ($operatoriGruppo as $associazione)
                                            <tr wire:key="operatore-{{ $gruppo }}-{{ $associazione->id }}">
                                                <td class="px-3 py-2">{{ $associazione->operatore?->codice ?: '—' }}</td>
                                                <td class="px-3 py-2">{{ trim(($associazione->operatore?->cognome ?? '').' '.($associazione->operatore?->nome ?? '')) ?: 'Operatore non disponibile' }}</td>
                                                <td class="px-3 py-2 text-right">{{ $associazione->operatore?->costo_orario ? number_format($associazione->operatore->costo_orario, 2, ',', '.').' €' : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
        </x-ui.modal>
    @endif
</div>

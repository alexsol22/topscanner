<div class="space-y-5">
    @if (session('esito'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-100">{{ session('esito') }}</div>
    @endif

    @if ($daEliminare)
        <x-ui.conferma-eliminazione :title="'Eliminare '.$daEliminare->nome_completo.'?'" atteso="conferma">
            @if ($riepilogo['turni'] > 0)
                <p class="rounded-xl bg-amber-50 p-3 font-medium text-amber-800 ring-1 ring-amber-100">Questo utente ha {{ $riepilogo['turni'] }} turni/registrazioni nello storico: l'eliminazione verrà bloccata. Usa «Disattiva» per impedirgli l'accesso mantenendo lo storico.</p>
            @endif
            <p>Verranno eliminati <strong>definitivamente</strong>:</p>
            <ul class="list-disc space-y-0.5 pl-5">
                <li>L'account di accesso (non potrà più entrare)</li>
                <li>{{ $riepilogo['documenti'] }} documenti, attestati e formazioni, con i file caricati</li>
                <li>{{ $riepilogo['materiali'] }} materiali assegnati</li>
                <li>Preferenze ed esclusioni nei clienti, avvisi e documenti letti</li>
                <li>Foto profilo</li>
            </ul>
            <p>Se vuoi solo impedire l'accesso, usa «Disattiva».</p>
        </x-ui.conferma-eliminazione>
    @endif

    @if (! $editing)
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 gap-2">
                <input type="search" wire:model.live.debounce.300ms="cerca" placeholder="Cerca per nome o email…" class="input sm:max-w-xs">
            </div>
            <button wire:click="nuovo" class="btn-primary">+ Nuovo utente</button>
        </div>

        <div class="card overflow-hidden"><div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sky-100">
                <thead class="bg-sky-50/60"><tr><th class="th">Nome</th><th class="th">Ruolo</th><th class="th">Contatti</th><th class="th">Stato</th><th class="th"></th></tr></thead>
                <tbody class="divide-y divide-sky-50">
                    @foreach ($utenti as $u)
                        <tr wire:key="u{{ $u->id }}" class="hover:bg-sky-50/40">
                            <td class="td"><p class="font-semibold text-slate-800">{{ $u->nome_completo }}</p><p class="text-xs text-slate-500">{{ $u->codice }}</p></td>
                            <td class="td">{{ $u->ruolo->label() }}<p class="text-xs text-slate-500">{{ $u->cliente?->ragione_sociale }}</p></td>
                            <td class="td"><p>{{ $u->email }}</p><p class="text-xs text-slate-500">{{ $u->cellulare }}</p></td>
                            <td class="td"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->attivo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $u->attivo ? 'Attivo' : 'Disattivo' }}</span></td>
                            <td class="td whitespace-nowrap text-right">
                                <button wire:click="modifica({{ $u->id }})" class="btn-soft !px-3 !py-1.5">Modifica</button>
                                @if ($u->id !== auth()->id())
                                    <button wire:click="attivaDisattiva({{ $u->id }})" class="btn-soft !px-3 !py-1.5">{{ $u->attivo ? 'Disattiva' : 'Attiva' }}</button>
                                    <button wire:click="richiediEliminazione({{ $u->id }})" class="btn-danger !px-3 !py-1.5">Elimina</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div></div>
        {{ $utenti->links() }}
    @endif

    @if ($editing)
        @php($isOperatore = ($f['ruolo'] ?? '') === 'operatore')
        <div class="card p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-800">{{ $userId ? 'Modifica utente' : 'Nuovo utente' }}</h2>
                <button type="button" wire:click="$set('editing', false)" class="btn-soft">← Torna all'elenco</button>
            </div>

            <div class="mb-4 flex gap-1 overflow-x-auto rounded-2xl bg-sky-50 p-1.5">
                @php($sezioni = ['dati' => 'Dati '.($isOperatore ? 'operatore '.($f['codice'] ?? '') : 'utente')])
                @if ($isOperatore)
                    @php($sezioni += ['formazioni' => 'Formazioni', 'attestati' => 'Documenti / Attestati', 'materiali' => 'Materiali / Abbigliamento'])
                @endif
                @foreach ($sezioni as $chiave => $etichetta)
                    <button type="button" wire:click="$set('tab', '{{ $chiave }}')" @disabled($chiave !== 'dati' && ! $userId)
                        class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition disabled:opacity-40 {{ $tab === $chiave ? 'bg-sky-500 text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">{{ $etichetta }}</button>
                @endforeach
            </div>

            <form wire:submit="salva" @class(['hidden' => $tab !== 'dati'])>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-ui.switch model="f.attivo" label="Abilita accesso" class="sm:col-span-2" />
                    <x-ui.field label="Colore" name="f.colore"><div class="flex items-center gap-2"><input type="color" wire:model.live="f.colore" class="h-10 w-14 cursor-pointer rounded-lg border border-sky-100 bg-white p-1"><input wire:model.live.debounce.400ms="f.colore" class="input" maxlength="7"></div></x-ui.field>
                    @if ($isOperatore)
                        <x-ui.field label="Nome in codice *" name="f.codice" class="sm:col-span-2 lg:col-span-3"><input wire:model="f.codice" class="input"></x-ui.field>
                    @endif
                    <x-ui.field label="Nome *" name="f.name"><input wire:model="f.name" class="input"></x-ui.field>
                    <x-ui.field label="Cognome *" name="f.cognome"><input wire:model="f.cognome" class="input"></x-ui.field>
                    <x-ui.field label="Email *" name="f.email"><input type="email" wire:model="f.email" class="input"></x-ui.field>
                    <x-ui.field label="Cellulare" name="f.cellulare"><input wire:model="f.cellulare" class="input"></x-ui.field>
                    @if ($isOperatore)
                        <x-ui.field label="Nato il" name="f.data_nascita"><input wire:model="f.data_nascita" class="input" maxlength="10" placeholder="gg/mm/aaaa"></x-ui.field>
                        <x-ui.field label="Nato a" name="f.luogo_nascita"><input wire:model="f.luogo_nascita" class="input"></x-ui.field>
                        <x-ui.field label="Indirizzo di residenza" name="f.indirizzo"><input wire:model="f.indirizzo" class="input"></x-ui.field>
                        <x-ui.field label="Provincia di residenza" name="f.provincia"><x-ui.select-search model="f.provincia" :options="$province->map(fn ($p) => ['id' => (string) $p->id, 'nome' => $p->sigla.' — '.$p->nome])->values()" placeholder="Scegli provincia" /></x-ui.field>
                        <x-ui.field label="Codice IBAN" name="f.iban"><input wire:model="f.iban" class="input"></x-ui.field>
                        <x-ui.field label="Costo orario" name="f.costo_orario"><input type="number" step="0.01" wire:model="f.costo_orario" class="input"></x-ui.field>
                    @endif
                    @if (auth()->user()->ruolo->value === 'admin' || auth()->user()->ruolo === \App\Enums\Ruolo::SuperAdmin)
                        <x-ui.field :label="$userId ? 'Nuova password (lascia vuoto per non cambiarla)' : 'Password (vuoto = generata)'" name="f.password"><input type="password" wire:model="f.password" class="input" autocomplete="new-password" minlength="8"></x-ui.field>
                    @endif
                    <x-ui.field label="Avatar / Fotografia" name="avatar"><input type="file" wire:model="avatar" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-sky-700"></x-ui.field>
                </div>
                <div class="mt-5 flex items-center justify-between gap-2">
                    <p class="text-xs text-slate-500">I campi contrassegnati con * sono obbligatori.</p>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('editing', false)" class="btn-soft">Annulla</button>
                        <button class="btn-primary" wire:loading.attr="disabled" wire:target="avatar">{{ $userId ? 'Aggiorna' : 'Inserisci' }}</button>
                    </div>
                </div>
            </form>

            @if ($tab === 'formazioni' && $userId)
                <div class="overflow-x-auto rounded-2xl ring-1 ring-sky-100">
                    <table class="w-full min-w-[56rem] text-left text-sm">
                        <thead class="bg-sky-50 text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Data inizio</th>
                                <th class="px-3 py-2">Data fine</th>
                                <th class="px-3 py-2">Titolo / Descrizione</th>
                                <th class="px-3 py-2">File</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-100">
                            @foreach ($righeFormazioni as $id => $riga)
                                <tr wire:key="fo{{ $id }}" class="align-top">
                                    <td class="px-3 py-2"><input type="date" wire:model="righeFormazioni.{{ $id }}.inizio" class="input">@error("righeFormazioni.$id.inizio")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                                    <td class="px-3 py-2"><input type="date" wire:model="righeFormazioni.{{ $id }}.fine" class="input">@error("righeFormazioni.$id.fine")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                                    <td class="px-3 py-2"><input wire:model="righeFormazioni.{{ $id }}.descrizione" class="input">@error("righeFormazioni.$id.descrizione")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('documenti.scarica', $id, false) }}" class="block truncate text-sky-700 hover:underline">Scarica file attuale</a>
                                        <input type="file" wire:model="fileRigheFormazioni.{{ $id }}" class="mt-1 block w-full text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-sky-100 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-sky-700" title="Scegli per sostituire il file">
                                        @error("fileRigheFormazioni.$id")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                        <button type="button" wire:click="salvaFormazione({{ $id }})" wire:loading.attr="disabled" wire:target="fileRigheFormazioni.{{ $id }},salvaFormazione" class="btn-primary !px-3 !py-1">Salva</button>
                                        <button type="button" wire:click="eliminaDocumento({{ $id }})" wire:confirm="Eliminare la formazione?" class="btn-danger !px-3 !py-1">Elimina</button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-sky-50/60 align-top">
                                <td class="px-3 py-2"><input type="date" wire:model="formazione.inizio" class="input">@error('formazione.inizio')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                                <td class="px-3 py-2"><input type="date" wire:model="formazione.fine" class="input">@error('formazione.fine')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                                <td class="px-3 py-2"><input wire:model="formazione.descrizione" class="input" placeholder="Nuova formazione: titolo">@error('formazione.descrizione')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                                <td class="px-3 py-2">
                                    <input type="file" wire:model="fileFormazione" class="block w-full text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-sky-100 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-sky-700">
                                    @error('fileFormazione')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                </td>
                                <td class="px-3 py-2 text-right"><button type="button" wire:click="aggiungiFormazione" wire:loading.attr="disabled" wire:target="fileFormazione,aggiungiFormazione" class="btn-primary !px-3 !py-1">Aggiungi</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-500">Modifica i campi nella riga e premi Salva. Per sostituire il file, sceglilo nella riga prima di salvare.</p>
            @endif

            @if ($tab === 'attestati' && $userId)
                <div class="space-y-4">
                    <ul class="divide-y divide-sky-100 rounded-2xl ring-1 ring-sky-100">
                        @forelse ($attestati as $d)
                            <li wire:key="at{{ $d->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                <div class="min-w-0">
                                    <select wire:change="aggiornaTipologia({{ $d->id }}, $event.target.value)" class="input !w-auto !py-1 font-medium">
                                        @foreach ($tipiCertificato as $t)
                                            <option value="{{ $t->id }}" @selected((string) $d->tipologia === (string) $t->id)>{{ $t->nome }}</option>
                                        @endforeach
                                    </select>
                                    @if ($d->descrizione) <span class="ml-2 text-slate-500">{{ $d->descrizione }}</span> @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="flex items-center gap-1 text-xs text-slate-500">Scadenza
                                        <input type="date" value="{{ $d->scadenza?->format('Y-m-d') }}" wire:change="aggiornaScadenza({{ $d->id }}, $event.target.value)" class="input !w-auto !py-1 {{ $d->inScadenza() ? '!border-rose-300 !text-rose-600' : '' }}">
                                    </label>
                                    <a href="{{ route('documenti.scarica', [$d, 'visualizza' => 1], false) }}" target="_blank" class="btn-soft !px-3 !py-1">Visualizza</a>
                                    <a href="{{ route('documenti.scarica', $d, false) }}" class="btn-soft !px-3 !py-1">Scarica</a>
                                    <button type="button" wire:click="eliminaDocumento({{ $d->id }})" wire:confirm="Eliminare il documento?" class="btn-danger !px-3 !py-1">Elimina</button>
                                </div>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-sm text-slate-500">Nessun documento.</li>
                        @endforelse
                    </ul>
                    <div class="grid gap-3 rounded-2xl bg-sky-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <x-ui.field label="Documento / Attestato" name="attestato.tipologia"><x-ui.select-search wire:key="tipo-attestato" model="attestato.tipologia" :options="$tipiCertificato->map(fn ($t) => ['id' => (string) $t->id, 'nome' => $t->nome])->values()" placeholder="Scegli tipo" /></x-ui.field>
                        <x-ui.field label="Descrizione" name="attestato.descrizione"><input wire:model="attestato.descrizione" class="input"></x-ui.field>
                        <x-ui.field label="Scadenza" name="attestato.scadenza"><input type="date" wire:model="attestato.scadenza" class="input"></x-ui.field>
                        <x-ui.field label="Allega file" name="fileAttestato"><input type="file" wire:model="fileAttestato" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-sky-700"></x-ui.field>
                        <button type="button" wire:click="aggiungiAttestato" wire:loading.attr="disabled" wire:target="fileAttestato,aggiungiAttestato" class="btn-primary sm:col-span-2 lg:col-span-4">Aggiungi documento</button>
                    </div>
                </div>
            @endif

            @if ($tab === 'materiali' && $userId)
                @php($opzioni = $tipiMateriale->map(fn ($t) => ['id' => (string) $t->id, 'nome' => $t->nome]))
                <div class="overflow-x-auto rounded-2xl ring-1 ring-sky-100">
                    <table class="w-full min-w-[56rem] text-left text-sm">
                        <thead class="bg-sky-50 text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Materiale / abbigliamento</th>
                                <th class="px-3 py-2">Descrizione</th>
                                <th class="px-3 py-2">Taglia</th>
                                <th class="px-3 py-2">Riconsegna</th>
                                <th class="px-3 py-2 text-center">Consegnato</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-100">
                            @foreach ($righeMateriali as $id => $riga)
                                @php($inScadenza = $riga['riconsegna'] !== '' && ! $riga['consegnato'] && \Illuminate\Support\Carbon::parse($riga['riconsegna'])->lte($limitePromemoria))
                                <tr wire:key="ma{{ $id }}" class="align-top">
                                    <td class="px-3 py-2">
                                        <select wire:model="righeMateriali.{{ $id }}.id_materiale" class="input">
                                            @foreach ($opzioni as $o)<option value="{{ $o['id'] }}">{{ $o['nome'] }}</option>@endforeach
                                        </select>
                                        @error("righeMateriali.$id.id_materiale")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="px-3 py-2"><input wire:model="righeMateriali.{{ $id }}.descrizione" class="input"></td>
                                    <td class="px-3 py-2"><input wire:model="righeMateriali.{{ $id }}.taglia" class="input"></td>
                                    <td class="px-3 py-2">
                                        <input type="date" wire:model="righeMateriali.{{ $id }}.riconsegna" class="input">
                                        @error("righeMateriali.$id.riconsegna")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                        @if ($inScadenza)
                                            @if (\Illuminate\Support\Carbon::parse($riga['riconsegna'])->lt(today()))
                                                <span class="mt-1 inline-block rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700" title="Riconsegna scaduta e materiale non consegnato: rientra nel promemoria automatico agli amministratori.">Scaduto</span>
                                            @else
                                                <span class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700" title="Riconsegna in scadenza e materiale non consegnato: rientra nel promemoria automatico agli amministratori.">In scadenza</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center"><input type="checkbox" wire:model="righeMateriali.{{ $id }}.consegnato" class="size-5 rounded border-slate-300 accent-emerald-500" title="Consegnato all'operatore"></td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                        <button type="button" wire:click="salvaMateriale({{ $id }})" class="btn-primary !px-3 !py-1">Salva</button>
                                        <button type="button" wire:click="eliminaMateriale({{ $id }})" wire:confirm="Eliminare il materiale?" class="btn-danger !px-3 !py-1">Elimina</button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-sky-50/60 align-top">
                                <td class="px-3 py-2">
                                    <select wire:model="materiale.id_materiale" class="input">
                                        <option value="">Nuovo: scegli materiale</option>
                                        @foreach ($opzioni as $o)<option value="{{ $o['id'] }}">{{ $o['nome'] }}</option>@endforeach
                                    </select>
                                    @error('materiale.id_materiale')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                </td>
                                <td class="px-3 py-2"><input wire:model="materiale.descrizione" class="input" placeholder="Descrizione"></td>
                                <td class="px-3 py-2"><input wire:model="materiale.taglia" class="input" placeholder="Taglia"></td>
                                <td class="px-3 py-2">
                                    <input type="date" wire:model="materiale.riconsegna" class="input">
                                    @error('materiale.riconsegna')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                </td>
                                <td class="px-3 py-2 text-center"><input type="checkbox" wire:model="materiale.consegnato" class="size-5 rounded border-slate-300 accent-emerald-500" title="Consegnato all'operatore"></td>
                                <td class="px-3 py-2 text-right"><button type="button" wire:click="aggiungiMateriale" class="btn-primary !px-3 !py-1">Aggiungi</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-500">Modifica i campi direttamente nella riga e premi Salva. Con data di riconsegna e materiale non consegnato, gli amministratori ricevono il promemoria automatico.</p>
            @endif
        </div>
    @endif
</div>

<div class="space-y-5">
    @if (session('esito'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-100">{{ session('esito') }}</div>
    @endif

    @if ($idCambioStato !== null && $statoRichiesto !== null)
        <x-ui.modal :title="$statoRichiesto ? 'Riabilitare account' : 'Disabilitare account'" close="annullaCambioStato">
            <div class="flex items-start gap-4">
                <div @class([
                    'flex size-12 shrink-0 items-center justify-center rounded-full',
                    'bg-emerald-100 text-emerald-600' => $statoRichiesto,
                    'bg-amber-100 text-amber-600' => ! $statoRichiesto,
                ])>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        @if ($statoRichiesto)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l4 4L19 6"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 003.2 21h17.6a1.6 1.6 0 001.4-2.4L13.7 3.9a2 2 0 00-3.4 0z"/>
                        @endif
                    </svg>
                </div>
                <div class="space-y-2 text-sm text-slate-600">
                    <p>
                        {{ $statoRichiesto ? 'Vuoi riabilitare' : 'Vuoi disabilitare' }}
                        l’account di <strong class="text-slate-800">{{ $nomeCambioStato }}</strong>?
                    </p>
                    @if ($statoRichiesto)
                        <p>Verrà generata una nuova password temporanea e inviata all’operatore via email.</p>
                    @else
                        <p>L’operatore non potrà più accedere finché l’account non verrà riabilitato.</p>
                    @endif
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" wire:click="annullaCambioStato" class="btn-soft">Annulla</button>
                <button type="button" wire:click="confermaCambioStato" wire:loading.attr="disabled" class="{{ $statoRichiesto ? 'btn-primary' : 'btn-danger' }}">
                    {{ $statoRichiesto ? 'Sì, riabilita' : 'Sì, disabilita' }}
                </button>
            </div>
        </x-ui.modal>
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

    @if ($modalFormazioni)
        <x-ui.modal :title="'Formazioni / Attestati — '.$codiceOperatoreFormazioni" wide="xl" close="chiudiModalFormazioni">
            <div class="overflow-x-auto rounded-2xl ring-1 ring-sky-100">
                <table class="w-full min-w-[36rem] text-left text-sm">
                    <thead class="bg-sky-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Data / Scadenza</th>
                            <th class="px-3 py-2">Formazione / Certificato - Attestato</th>
                            <th class="px-3 py-2 text-center">Scarica</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sky-100">
                        @forelse ($documentiOperatore as $documento)
                            <tr wire:key="documento-operatore-{{ $documento['id'] }}">
                                <td class="whitespace-nowrap px-3 py-3">{{ $documento['data'] }}</td>
                                <td class="px-3 py-3">{{ $documento['descrizione'] ?: '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($documento['file'])
                                        <a href="{{ route('documenti.scarica', $documento['id'], false) }}" class="btn-soft !px-3 !py-1">Scarica</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">Nessuna formazione o attestato.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="button" wire:click="chiudiModalFormazioni" class="btn-soft">Chiudi</button>
            </div>
        </x-ui.modal>
    @endif

    @if (! $editing)
        <div class="card overflow-hidden">
            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-slate-800">Operatori</h2>
                <div class="flex items-center justify-end gap-2">
                    <x-ui.filtri title="Ricerca operatore" trigger-label="Ricerca operatore" :values="['filtroCodice' => $filtroCodice, 'filtroNome' => $filtroNome, 'filtroCognome' => $filtroCognome, 'filtroEmail' => $filtroEmail, 'filtroCellulare' => $filtroCellulare]">
                        <x-slot:trigger>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg>
                        </x-slot:trigger>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="filtro-codice-operatore" class="label">Codice</label>
                                <input id="filtro-codice-operatore" type="search" x-model="valori.filtroCodice" class="input">
                            </div>
                            <div>
                                <label for="filtro-nome-operatore" class="label">Nome</label>
                                <input id="filtro-nome-operatore" type="search" x-model="valori.filtroNome" class="input">
                            </div>
                            <div>
                                <label for="filtro-cognome-operatore" class="label">Cognome</label>
                                <input id="filtro-cognome-operatore" type="search" x-model="valori.filtroCognome" class="input">
                            </div>
                            <div>
                                <label for="filtro-email-operatore" class="label">Email</label>
                                <input id="filtro-email-operatore" type="search" x-model="valori.filtroEmail" class="input">
                            </div>
                            <div>
                                <label for="filtro-cellulare-operatore" class="label">Cellulare</label>
                                <input id="filtro-cellulare-operatore" type="search" inputmode="tel" x-model="valori.filtroCellulare" class="input">
                            </div>
                        </div>
                    </x-ui.filtri>
                    <button type="button" wire:click="nuovo" title="Aggiungi operatore" aria-label="Aggiungi operatore" class="btn-primary !p-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    </button>
                </div>
            </div>
        </div>

        @if ($utenti->isNotEmpty())
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($utenti as $u)
                    @php($isOperatore = (int) $u->user_type === 4)
                    <article wire:key="u{{ $u->id }}" class="card flex flex-col items-center px-5 pt-5 text-center">
                        <x-ui.avatar :user="$u" class="h-36 w-36 border-4 border-white shadow-sm ring-1 ring-sky-100" />
                        <div class="flex flex-1 flex-col items-center pt-3">
                            @if ($isOperatore)
                                <h3 class="text-lg font-bold text-slate-800">
                                    <span class="block text-sky-700">{{ $u->codice }}</span>
                                    <span>{{ $u->cognome }} <small class="font-normal text-slate-500">{{ $u->nome }}</small></span>
                                </h3>
                            @else
                                <h3 class="text-lg font-bold text-slate-800">{{ $u->nome_completo }}</h3>
                                <p class="text-xs text-slate-500">{{ $u->ruolo->label() }}{{ $u->cliente ? ' — '.$u->cliente->ragione_sociale : '' }}</p>
                            @endif
                            <p class="mt-4 flex items-center justify-center gap-2 break-all text-sm text-slate-600">
                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ $u->email }}
                            </p>
                            <p class="mt-2 flex items-center justify-center gap-2 text-sm text-slate-600">
                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3l2 5-2 1a11 11 0 005 5l1-2 5 2v3a2 2 0 01-2 2A14 14 0 013 5z"/></svg>
                                {{ $u->cellulare ?: '—' }}
                            </p>
                        </div>
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-2 border-t border-sky-50 py-4">
                            @if ($isOperatore && $u->documenti_count > 0)
                                <button type="button" wire:click="mostraFormazioni({{ $u->id }})" title="Visualizza formazioni" aria-label="Visualizza formazioni di {{ $u->nome_completo }}" class="btn-soft !p-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 12v5c4.5 3.5 9.5 3.5 14 0v-5m3-3v6"/></svg>
                                </button>
                            @endif
                            @if ($isOperatore && $u->turni_count > 0)
                                <a href="{{ route('turni', ['operatore' => $u->id]) }}" wire:navigate title="Visualizza agenda" aria-label="Visualizza agenda di {{ $u->nome_completo }}" class="btn-soft !p-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                                </a>
                            @endif
                            @if ($isOperatore && $u->gda_count > 0)
                                <a href="{{ route('presenze', ['operatore' => $u->id]) }}" wire:navigate title="Visualizza GDA" aria-label="Visualizza GDA di {{ $u->nome_completo }}" class="btn-soft !p-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                </a>
                            @endif
                            @if (auth()->user()->isStaff())
                                <button type="button" wire:click="modifica({{ $u->id }})" title="Modifica operatore" aria-label="Modifica {{ $u->nome_completo }}" class="btn-soft !p-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l2.651 2.651M5 19l4.25-.85L19.513 7.887a1.875 1.875 0 00-2.65-2.65L6.6 15.5 5 19z"/></svg>
                                </button>
                                @if ($u->id !== auth()->id())
                                    <button type="button" wire:click="richiediEliminazione({{ $u->id }})" title="Elimina operatore" aria-label="Elimina {{ $u->nome_completo }}" class="btn-danger !p-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-10 0V5h8v2m-9 0l1 13h8l1-13M10 11v5m4-5v5"/></svg>
                                    </button>
                                    <button type="button" wire:click="richiediCambioStato({{ $u->id }}, {{ $u->attivo ? 'false' : 'true' }})" title="{{ $u->attivo ? 'Disattiva account' : 'Attiva account' }}" aria-label="{{ $u->attivo ? 'Disattiva' : 'Attiva' }} {{ $u->nome_completo }}" class="rounded-xl p-2 text-white transition {{ $u->attivo ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-rose-500 hover:bg-rose-600' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $u->attivo ? 'M5 12l4 4L19 6' : 'M6 6l12 12M18 6L6 18' }}"/></svg>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($hasMoreUsers)
                <div wire:intersect="caricaAltri" class="flex min-h-12 items-center justify-center py-4 text-sm text-slate-500" role="status">
                    <span wire:loading.remove wire:target="caricaAltri">Scorri per caricare altri operatori</span>
                    <span wire:loading wire:target="caricaAltri" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Caricamento...
                    </span>
                </div>
            @endif
        @else
            <div class="rounded-xl border-l-4 border-cyan-500 bg-cyan-50 px-4 py-3 text-sm italic text-slate-600" role="status">
                Non sono stati trovati operatori che soddisfino i parametri di ricerca inseriti.
            </div>
        @endif
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

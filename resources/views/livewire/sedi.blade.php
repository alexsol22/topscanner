<div class="space-y-5">
    @if (session('esito'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-100">{{ session('esito') }}</div>
    @endif
    @if (session('avviso'))
        <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-100">{{ session('avviso') }}</div>
    @endif

    @if ($daEliminare)
        <x-ui.conferma-eliminazione :title="'Eliminare '.$daEliminare->descrizione.'?'" atteso="conferma">
            <p>Verranno eliminati <strong>definitivamente</strong>:</p>
            <ul class="list-disc space-y-0.5 pl-5">
                <li>La sede e i suoi documenti</li>
                <li>{{ $riepilogo['turni'] }} turni (con i relativi step)</li>
                <li>{{ $riepilogo['postazioni'] }} postazioni, con foto e file caricati</li>
                <li>La cartella della sede; i codici QR tornano disponibili</li>
            </ul>
            @if ($riepilogo['presenze'] > 0)
                <p class="rounded-xl bg-amber-50 p-3 font-medium text-amber-800 ring-1 ring-amber-100">Sono presenti {{ $riepilogo['presenze'] }} registrazioni di presenza (GDA): resteranno nello storico ma non saranno più collegate a una sede.</p>
            @endif
        </x-ui.conferma-eliminazione>
    @endif
    @if (! $clienteCorrente)
        <div class="card flex flex-col items-start gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Seleziona un cliente</h2>
                <p class="mt-1 text-sm text-slate-500">Apri le sedi dal link presente nella scheda del cliente.</p>
            </div>
            @if (auth()->user()->isStaff())
                <a href="{{ route('clienti') }}" wire:navigate class="btn-primary">Vai ai clienti</a>
            @endif
        </div>
    @else
        <div class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="min-w-0">
                @if (auth()->user()->isStaff())
                    <a href="{{ route('clienti') }}" wire:navigate class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-sky-700 hover:underline">← Torna ai clienti</a>
                @endif
                <p class="text-xs font-bold uppercase tracking-wider text-sky-600">{{ $clienteCorrente->ragione_sociale }}</p>
                <h2 class="mt-1 text-xl font-bold text-slate-800">Elenco sedi</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $sedi->total() }} {{ $sedi->total() === 1 ? 'sede' : 'sedi' }} associate</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if (auth()->user()->isStaff())
                    <button wire:click="esporta" class="btn-soft">Esporta CSV</button>
                    <a href="{{ route('sedi.crea', ['cliente' => $clienteCorrente->id]) }}" wire:navigate class="btn-primary">+ Nuova sede</a>
                @endif
            </div>
        </div>

        <x-ui.filtri :values="['filtroDenominazione' => $filtroDenominazione, 'filtroEmail' => $filtroEmail, 'filtroTelefono' => $filtroTelefono]">
          <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field label="Denominazione" name="filtroDenominazione">
                <input type="search" x-model="valori.filtroDenominazione" class="input" placeholder="Cerca per denominazione">
            </x-ui.field>
            <x-ui.field label="Email" name="filtroEmail">
                <input type="search" x-model="valori.filtroEmail" class="input" placeholder="Cerca per email">
            </x-ui.field>
            <x-ui.field label="Telefono" name="filtroTelefono">
                <input type="search" x-model="valori.filtroTelefono" class="input" placeholder="Cerca per telefono" inputmode="tel">
            </x-ui.field>
          </div>
        </x-ui.filtri>

        <div class="space-y-4">
            @forelse ($sedi as $s)
                <article wire:key="s{{ $s->id }}" class="card overflow-hidden">
                    <div class="grid md:grid-cols-[12rem_minmax(0,1fr)]">
                        <div x-data="{ fotoDisponibile: @js((bool) $s->foto) }" class="relative flex min-h-48 items-center justify-center bg-sky-50 text-sky-500 md:min-h-full">
                            @if ($s->foto)
                                <img x-show="fotoDisponibile" x-on:error="fotoDisponibile = false" src="{{ route('sedi.foto', $s->id) }}" alt="Foto di {{ $s->descrizione }}" class="absolute inset-0 h-full w-full object-cover">
                            @endif
                            <svg x-cloak x-show="!fotoDisponibile" xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4M9 10h.01M12 10h.01M15 10h.01M9 13h.01M12 13h.01M15 13h.01"/>
                            </svg>
                        </div>
                        <div class="flex min-w-0 flex-col p-5 sm:p-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <h3 class="truncate text-xl font-bold text-slate-800">{{ $s->descrizione }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ trim($s->indirizzo.' '.$s->cap.' '.$s->citta.' '.$s->provincia_sigla) ?: 'Indirizzo non disponibile' }}</p>
                                </div>
                                @if ($s->codice)
                                    <span class="shrink-0 rounded-lg bg-slate-50 px-3 py-1.5 font-mono text-xs text-slate-500">{{ $s->codice->codice }}</span>
                                @endif
                            </div>

                            <div class="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                @if ($s->email)<p class="truncate"><span class="font-medium text-slate-700">Email:</span> {{ $s->email }}</p>@endif
                                @if ($s->telefono)<p><span class="font-medium text-slate-700">Telefono:</span> {{ $s->telefono }}</p>@endif
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2 border-t border-sky-50 pt-4">
                                @if ($s->turni_count > 0)
                                    <a href="{{ route('turni', ['sede' => $s->id]) }}" wire:navigate class="btn-soft !px-3 !py-1.5">Visualizza agenda pos</a>
                                @endif
                                @if ($s->turni_conclusi_count > 0)
                                    <a href="{{ route('presenze', ['sede' => $s->id]) }}" wire:navigate class="btn-soft !px-3 !py-1.5">Visualizza GDA</a>
                                @endif
                                <button wire:click="postazioni({{ $s->id }})" class="btn-soft !px-3 !py-1.5">Scarica QR</button>
                                @if (auth()->user()->isStaff())
                                    <a href="{{ route('sedi.modifica', ['sede' => $s, 'cliente' => $clienteCorrente->id]) }}" wire:navigate class="btn-soft !px-3 !py-1.5">Modifica sede</a>
                                    <button wire:click="richiediEliminazione({{ $s->id }})" class="btn-danger !px-3 !py-1.5">Elimina sede</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <p class="card p-10 text-center text-sm text-slate-500">Nessuna sede trovata per {{ $clienteCorrente->ragione_sociale }}.</p>
            @endforelse
        </div>
        {{ $sedi->links() }}
    @endif

    @if ($modalPostazioni && $sedeCorrente)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center sm:p-4">
            <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-2xl sm:rounded-3xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Qrcode {{ $sedeCorrente->descrizione }}</h3>
                    <button wire:click="$set('modalPostazioni', false)" class="rounded-lg p-1 text-slate-400 hover:bg-sky-50">✕</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-sky-100 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th scope="col" class="px-4 py-3">Postazione</th>
                                <th scope="col" class="w-20 px-4 py-3 text-center">Scarica</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50">
                            <tr>
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="font-semibold">Postazione principale</span><br>
                                    <span>{{ strtoupper(trim(implode(' ', array_filter([$sedeCorrente->indirizzo, $sedeCorrente->cap, $sedeCorrente->comune?->comune, $sedeCorrente->provincia?->sigla ? '('.$sedeCorrente->provincia->sigla.')' : null])))) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($sedeCorrente->codice)
                                        <a href="{{ route('sedi.qrcode', $sedeCorrente) }}" aria-label="Scarica QR postazione principale" class="inline-flex rounded-lg p-2 text-sky-700 hover:bg-sky-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @foreach ($sedeCorrente->postazioni as $post)
                                <tr wire:key="qr-postazione-{{ $post->id }}">
                                    <td class="px-4 py-3 text-slate-700">{{ $post->titolo }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($post->codice)
                                            <a href="{{ route('postazioni.qrcode', $post) }}" aria-label="Scarica QR {{ $post->titolo }}" class="inline-flex rounded-lg p-2 text-sky-700 hover:bg-sky-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

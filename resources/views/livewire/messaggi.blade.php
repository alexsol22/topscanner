<div class="space-y-5">
    <h2 class="text-lg font-semibold text-slate-800">{{ $canale === 'sms' ? 'SMS inviati' : 'Email inviate' }}</h2>

    @if (in_array($canale, ['sms', 'email'], true))
            <x-ui.filtri title="Filtri invii" :values="['filtroOperatore' => $filtroOperatore, 'filtroDal' => $filtroDal, 'filtroAl' => $filtroAl]">
              <section aria-label="Filtri invii" class="grid gap-3 sm:grid-cols-2">
                <x-ui.select-search
                    label="Operatore"
                    model="filtroOperatore"
                    :draft="true"
                    placeholder="Tutti gli operatori"
                    :options="$operatori->map(fn ($o) => ['id' => (string) $o->id, 'nome' => $o->nome_completo])->values()"
                />
                <div>
                    <label for="filtro-dal" class="label">Dal</label>
                    <input id="filtro-dal" type="datetime-local" x-model="valori.filtroDal" :max="valori.filtroAl || null" class="input">
                    @error('filtroDal') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="filtro-al" class="label">Al</label>
                    <input id="filtro-al" type="datetime-local" x-model="valori.filtroAl" :min="valori.filtroDal || null" class="input">
                    @error('filtroAl') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </section>
            </x-ui.filtri>
    @endif

    @if ($canale === 'sms')
            <section aria-label="SMS inviati" class="card overflow-hidden">
                @if ($crediti !== null)<p class="border-b border-sky-100 px-5 py-3 text-sm text-slate-600">Credito residuo: <b class="text-sky-600">{{ $crediti }} &euro;</b></p>@endif
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sky-100">
                        <thead class="bg-sky-50/60">
                            <tr>
                                <th class="th">Data</th>
                                <th class="th">Operatore</th>
                                <th class="th">Cellulare</th>
                                <th class="th">Mittente</th>
                                <th class="th">Testo</th>
                                <th class="th">Costo</th>
                                <th class="th">Stato</th>
                                <th class="th">Notifica</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50">
                            @forelse ($smsInviati as $sms)
                                @php
                                    $risultato = $sms->risultati->first();
                                    $notifica = $sms->risultati->flatMap(fn ($r) => $r->consegne)->sortByDesc('delivery_data')->first();
                                    $consegne = $sms->risultati->flatMap(fn ($r) => $r->consegne);
                                    $consegnato = $consegne->isNotEmpty() && $consegne->every(fn ($consegna) => $consegna->delivery_status === 'D');
                                    $consegnaParziale = $consegne->contains(fn ($consegna) => $consegna->delivery_status === 'D')
                                        && $consegne->contains(fn ($consegna) => $consegna->delivery_status !== 'D');
                                @endphp
                                <tr wire:key="sms-{{ $sms->id }}" class="align-top hover:bg-sky-50/40">
                                    <td class="td whitespace-nowrap">{{ $sms->data_invio?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="td whitespace-nowrap">{{ $sms->operatore?->nome_completo ?? '—' }}</td>
                                    <td class="td whitespace-nowrap">{{ $sms->destinatari }}</td>
                                    <td class="td whitespace-nowrap">{{ $sms->sender ?? '—' }}</td>
                                    <td class="td min-w-64 max-w-xl whitespace-pre-wrap break-words">{{ $sms->testo }}</td>
                                    <td class="td whitespace-nowrap">{{ $sms->risultati->sum('costo') > 0 ? number_format($sms->risultati->sum('costo'), 2, ',', '.').' €' : '—' }}</td>
                                    <td class="td whitespace-nowrap">
                                        @php($descrizioneStato = $sms->sent === 'Y' ? 'SMS inviato' : ($risultato?->response ?: 'SMS non inviato'))
                                        <div x-data="{ mostraLegenda: false }" @click.outside="mostraLegenda = false" class="relative inline-block">
                                            <button
                                                type="button"
                                                @click="mostraLegenda = ! mostraLegenda"
                                                :aria-expanded="mostraLegenda"
                                                aria-controls="sms-stato-{{ $sms->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-sky-300 {{ $sms->sent === 'Y' ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}"
                                            >
                                                @if ($sms->sent === 'Y')
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                                                    </svg>
                                                    Inviato
                                                @else
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18"/>
                                                    </svg>
                                                    Non inviato
                                                @endif
                                            </button>
                                            <div
                                                id="sms-stato-{{ $sms->id }}"
                                                x-cloak
                                                x-show="mostraLegenda"
                                                role="tooltip"
                                                style="position: absolute; z-index: 20; top: 100%; left: 0; width: 14rem; max-width: calc(100vw - 2rem); max-height: 6rem; overflow-y: auto; margin-top: .25rem; padding: .5rem .75rem; border-radius: .5rem; background: #0f172a; color: #fff; font-size: .75rem; line-height: 1.25rem; white-space: normal; overflow-wrap: anywhere; text-align: left; box-shadow: 0 10px 15px -3px rgb(15 23 42 / .3);"
                                            ><strong style="color: #fff;">{{ $sms->sent === 'Y' ? 'Inviato' : 'Non inviato' }}</strong>@if ($sms->sent !== 'Y' && $risultato?->response)<span style="display: block; color: #fff;">{{ $risultato->response }}</span>@endif</div>
                                        </div>
                                    </td>
                                    <td class="td whitespace-nowrap">
                                        @if ($notifica)
                                            <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-700' => $consegnato, 'bg-amber-100 text-amber-700' => $consegnaParziale, 'bg-rose-100 text-rose-700' => ! $consegnato && ! $consegnaParziale])>
                                                {{ $consegnato ? 'Consegnato' : ($consegnaParziale ? 'Parziale' : 'Fallita') }}
                                            </span>
                                            @if ($notifica->delivery_data)
                                                <span class="mt-1 block text-xs text-slate-400">{{ $notifica->delivery_data->format('d/m/Y H:i') }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="td py-10 text-center text-slate-500">Nessun SMS inviato.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $smsInviati->links() }}
    @elseif ($canale === 'email')
            <section aria-label="Email inviate" class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sky-100">
                        <thead class="bg-sky-50/60">
                            <tr>
                                <th class="th">Data</th>
                                <th class="th">Operatore</th>
                                <th class="th">Email</th>
                                <th class="th">Oggetto</th>
                                <th class="th">Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50">
                            @forelse ($emailInviate as $email)
                                <tr wire:key="email-{{ $email->id }}" class="hover:bg-sky-50/40">
                                    <td class="td whitespace-nowrap">{{ $email->data_invio?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="td whitespace-nowrap">{{ $email->operatore?->nome_completo ?? '—' }}</td>
                                    <td class="td max-w-xs whitespace-normal break-all">{{ $email->destinatario }}</td>
                                    <td class="td max-w-sm whitespace-normal">{{ $email->oggetto ?? '—' }}</td>
                                    <td class="td whitespace-nowrap">
                                        <button type="button" wire:click="visualizzaEmail({{ $email->id }})" class="btn-soft !px-3 !py-1.5">Visualizza</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="td py-10 text-center text-slate-500">Nessuna email inviata.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $emailInviate->links() }}
    @else
        <section class="card p-10 text-center">
            <h2 class="text-lg font-semibold text-slate-800">WhatsApp non ancora disponibile</h2>
            <p class="mt-2 text-sm text-slate-500">La consultazione dei messaggi WhatsApp sarà disponibile quando il canale verrà implementato.</p>
        </section>
    @endif

    @if ($modalEmail)
        <x-ui.modal title="Email inviata" wide="xl" close="chiudiEmail">
            <iframe
                title="Contenuto dell'email inviata"
                sandbox=""
                referrerpolicy="no-referrer"
                class="h-[70vh] w-full rounded-xl border border-sky-100 bg-[#fff]"
                srcdoc="{{ $emailVisualizzazione }}"
            ></iframe>
            <div class="mt-4 flex justify-end">
                <button type="button" wire:click="chiudiEmail" class="btn-soft">Chiudi</button>
            </div>
        </x-ui.modal>
    @endif
</div>

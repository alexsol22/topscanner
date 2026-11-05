@php
    $tabs = [
        'dati' => ['Dati sede', 'M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M12 10h.01M15 10h.01M9 13h.01M12 13h.01M15 13h.01M10 21v-4h4v4', ['f.cliente_id', 'f.descrizione', 'f.colore', 'f.telefono', 'f.email', 'f.note', 'f.distanza_max', 'foto']],
        'posizione' => ['Posizione', 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z', ['f.indirizzo', 'f.cap', 'f.citta', 'f.provincia', 'f.lat', 'f.lng']],
        'pos' => ['Pos', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', ['f.tariffa', 'f.pos', 'f.inizio_contratto', 'f.fine_contratto', 'ore.*']],
        'postazioni' => ['Postazioni', 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', ['postazioni.*', 'immaginiPostazioni.*']],
    ];
@endphp
<div class="space-y-5" x-data="{ tab: 'dati' }" @tab-errore.window="tab = $event.detail.tab">
    <a href="{{ route('sedi') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-sky-700 hover:underline">← Torna alle sedi</a>

    <div class="flex gap-1 overflow-x-auto rounded-2xl bg-white p-1.5 shadow-sm ring-1 ring-sky-100">
        @foreach ($tabs as $chiave => [$etichetta, $icona, $campi])
            <button type="button" @click="tab = '{{ $chiave }}'; $dispatch('tab-{{ $chiave }}')"
                :class="tab === '{{ $chiave }}' ? 'bg-sky-500 text-white shadow-sm' : 'text-slate-600 hover:bg-sky-50 hover:text-sky-700'"
                class="relative flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icona }}"/></svg>
                {{ $etichetta }}
                @if (collect($errors->keys())->contains(fn ($k) => collect($campi)->contains(fn ($c) => $c === $k || (str_ends_with($c, '.*') && str_starts_with($k, substr($c, 0, -1)))))) <span class="absolute right-2 top-1.5 h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white"></span> @endif
            </button>
        @endforeach
    </div>

    <form wire:submit="salva" class="space-y-5">
        <section x-show="tab === 'dati'" class="card p-5 sm:p-6">
            <h2 class="mb-4 text-base font-bold text-slate-800">Dati sede</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Qrcode associato" name="codice"><input value="{{ $codiceSede ?: 'Verrà generato al salvataggio' }}" class="input bg-slate-50 text-slate-500" readonly></x-ui.field>
                <x-ui.field label="Distanza massima scansione" name="f.distanza_max">
                    <div class="relative"><input type="number" wire:model.live.debounce.400ms="f.distanza_max" class="input !pr-10"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">m</span></div>
                </x-ui.field>
                <x-ui.field label="Cliente *" name="f.cliente_id"><select wire:model="f.cliente_id" class="input" @disabled($sedeId)><option value="">—</option>@foreach ($clienti as $c)<option value="{{ $c->id }}">{{ $c->ragione_sociale }}</option>@endforeach</select></x-ui.field>
                <div class="grid grid-cols-[1fr_auto] items-start gap-3">
                    <x-ui.field label="Denominazione *" name="f.descrizione"><input wire:model="f.descrizione" class="input"></x-ui.field>
                    <x-ui.field label="Colore" name="f.colore"><input type="color" wire:model.live="f.colore" class="h-10 w-14 cursor-pointer rounded-lg border border-sky-100 bg-white p-1"></x-ui.field>
                </div>
                <x-ui.field label="Email" name="f.email"><input type="email" wire:model="f.email" class="input" placeholder="Indirizzo email"></x-ui.field>
                <x-ui.field label="Telefono" name="f.telefono"><input wire:model="f.telefono" class="input" placeholder="Telefono"></x-ui.field>
                <x-ui.field label="Note" name="f.note" class="sm:col-span-2"><textarea wire:model="f.note" rows="2" class="input"></textarea></x-ui.field>
                <x-ui.field label="Fotografia sede" name="foto" class="sm:col-span-2">
                    <div class="flex items-center gap-3">
                        @if ($foto)
                            <img src="{{ $foto->temporaryUrl() }}" alt="" class="h-14 w-14 rounded-xl object-cover ring-1 ring-sky-100">
                        @elseif ($sedeId && $fotoSede)
                            <a href="{{ route('sedi.foto', $sedeId) }}" target="_blank"><img src="{{ route('sedi.foto', $sedeId) }}" alt="" class="h-14 w-14 rounded-xl object-cover ring-1 ring-sky-100"></a>
                        @endif
                        <input type="file" wire:model="foto" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
                    </div>
                </x-ui.field>
            </div>
        </section>

        <section x-show="tab === 'posizione'" x-cloak class="card p-5 sm:p-6">
            <h2 class="mb-4 text-base font-bold text-slate-800">Posizione</h2>
            <div class="grid gap-5 lg:grid-cols-5">
                <div class="lg:col-span-3">
                <div wire:ignore @tab-posizione.window="setTimeout(() => { if (mappa) { google.maps.event.trigger(mappa, 'resize'); const c = marker.getPosition(); if (c) { mappa.setCenter(c); } } }, 50)"
                    x-data="{
                        mappa: null, marker: null, cerchio: null, errore: false, suggerimenti: [], timer: null, token: null,
                        async init() {
                            const key = @js(config('services.google_maps.key'));
                            if (!key) { this.errore = true; return; }
                            window.gmapsPronta ??= new Promise((ok, ko) => {
                                if (window.google?.maps?.importLibrary) { return ok(); }
                                window.__gmapsCb = ok;
                                const s = document.createElement('script');
                                s.src = 'https://maps.googleapis.com/maps/api/js?key=' + key + '&loading=async&callback=__gmapsCb';
                                s.onerror = ko;
                                document.head.appendChild(s);
                            });
                            try {
                                await window.gmapsPronta;
                                const { Map } = await google.maps.importLibrary('maps');
                                const { Marker } = await google.maps.importLibrary('marker');
                                await google.maps.importLibrary('places');
                                await google.maps.importLibrary('geocoding');
                                this.token = new google.maps.places.AutocompleteSessionToken();
                                const lat = parseFloat($wire.f.lat), lng = parseFloat($wire.f.lng);
                                const ha = !isNaN(lat) && !isNaN(lng);
                                this.mappa = new Map(this.$refs.mappa, { center: ha ? { lat, lng } : { lat: 41.9, lng: 12.5 }, zoom: ha ? 17 : 6, streetViewControl: false, mapTypeControl: false });
                                this.marker = new Marker({ map: this.mappa, position: ha ? { lat, lng } : null, draggable: true });
                                const raggio = () => Math.min(5000, Math.max(10, parseInt($wire.f.distanza_max) || 100));
                                this.cerchio = new google.maps.Circle({ map: this.mappa, center: ha ? { lat, lng } : null, radius: raggio(), editable: true, strokeColor: '#0ea5e9', strokeWeight: 2, fillColor: '#0ea5e9', fillOpacity: 0.15, clickable: false });
                                this.cerchio.addListener('radius_changed', () => {
                                    const r = Math.min(5000, Math.max(10, Math.round(this.cerchio.getRadius())));
                                    if (r !== parseInt($wire.f.distanza_max)) { $wire.set('f.distanza_max', r); }
                                });
                                this.cerchio.addListener('center_changed', () => { const c = this.cerchio.getCenter(); if (c && this.marker.getPosition() && !c.equals(this.marker.getPosition())) { this.cerchio.setCenter(this.marker.getPosition()); } });
                                Alpine.effect(() => { const r = raggio(); if (this.cerchio && Math.round(this.cerchio.getRadius()) !== r) { this.cerchio.setRadius(r); } });
                                this.mappa.addListener('click', e => this.scegli(e.latLng, true));
                                this.marker.addListener('dragend', e => this.scegli(e.latLng, true));
                                this.marker.addListener('drag', e => this.cerchio.setCenter(e.latLng));
                            } catch (e) { this.errore = true; }
                        },
                        cerca() {
                            clearTimeout(this.timer);
                            const q = document.getElementById('sede-indirizzo').value.trim();
                            if (q.length < 3 || this.errore) { this.suggerimenti = []; return; }
                            this.timer = setTimeout(async () => {
                                try {
                                    const r = await google.maps.places.AutocompleteSuggestion.fetchAutocompleteSuggestions({ input: q, includedRegionCodes: ['it'], sessionToken: this.token });
                                    this.suggerimenti = r.suggestions.map(x => x.placePrediction);
                                } catch (e) { this.suggerimenti = []; }
                            }, 250);
                        },
                        async scelta(pred) {
                            this.suggerimenti = [];
                            const place = pred.toPlace();
                            await place.fetchFields({ fields: ['location', 'addressComponents'] });
                            this.token = new google.maps.places.AutocompleteSessionToken();
                            this.scegli(place.location, false);
                            this.indirizzo(place.addressComponents.map(a => ({ types: a.types, long_name: a.longText, short_name: a.shortText })));
                            this.mappa.setZoom(17);
                        },
                        scegli(pos, inverso) {
                            this.marker.setPosition(pos);
                            this.cerchio.setCenter(pos);
                            this.mappa.panTo(pos);
                            $wire.set('f.lat', pos.lat().toFixed(7));
                            $wire.set('f.lng', pos.lng().toFixed(7));
                            if (inverso) {
                                new google.maps.Geocoder().geocode({ location: pos }, (r, st) => { if (st === 'OK' && r[0]) { this.indirizzo(r[0].address_components); } });
                            }
                        },
                        indirizzo(comp) {
                            const c = (t, n = 'long_name') => (comp.find(a => a.types.includes(t)) || {})[n] || '';
                            const via = (c('route') + ' ' + c('street_number')).trim();
                            if (via) { $wire.set('f.indirizzo', via); const i = document.getElementById('sede-indirizzo'); if (i) { i.value = via; } }
                            $wire.set('f.cap', c('postal_code'));
                            $wire.set('f.citta', c('locality') || c('administrative_area_level_3'));
                            $wire.set('f.provincia', c('administrative_area_level_2', 'short_name'));
                        }
                    }">
                    <label class="label">Indirizzo</label>
                    <div class="relative" @click.outside="suggerimenti = []">
                        <input id="sede-indirizzo" value="{{ $f['indirizzo'] ?? '' }}" @input="$wire.set('f.indirizzo', $event.target.value, false); cerca()" class="input" autocomplete="off" placeholder="Inizia a scrivere l'indirizzo...">
                        <ul x-show="suggerimenti.length" x-cloak class="absolute inset-x-0 top-full z-50 mt-1 overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-sky-100">
                            <template x-for="p in suggerimenti" :key="p.placeId">
                                <li><button type="button" @click="scelta(p)" class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-sky-50" x-text="p.text.text"></button></li>
                            </template>
                        </ul>
                    </div>
                    <div x-ref="mappa" x-show="!errore" class="mt-3" style="height:22rem;width:100%;border-radius:.75rem;border:1px solid #e0f2fe"></div>
                    <p x-show="!errore" class="mt-1 text-xs text-slate-500">Scegli un suggerimento, clicca sulla mappa o trascina il marker per la posizione. Trascina il punto sul bordo del cerchio per impostare il raggio d'azione.</p>
                    <p x-show="errore" x-cloak class="rounded-lg bg-amber-50 p-3 text-xs text-amber-700">Mappa non disponibile: inserisci latitudine e longitudine manualmente.</p>
                </div>
                </div>
                <div class="grid content-start gap-4 sm:grid-cols-2 lg:col-span-2">
                    <x-ui.field label="CAP" name="f.cap"><input wire:model="f.cap" class="input"></x-ui.field>
                    <x-ui.field label="Provincia" name="f.provincia"><input wire:model="f.provincia" class="input" maxlength="2"></x-ui.field>
                    <x-ui.field label="Città" name="f.citta" class="sm:col-span-2"><input wire:model="f.citta" class="input"></x-ui.field>
                    <x-ui.field label="Latitudine" name="f.lat"><input wire:model="f.lat" class="input"></x-ui.field>
                    <x-ui.field label="Longitudine" name="f.lng"><input wire:model="f.lng" class="input"></x-ui.field>
                </div>
            </div>
        </section>

        <section x-show="tab === 'pos'" x-cloak class="card p-5 sm:p-6">
            <h2 class="mb-4 text-base font-bold text-slate-800">Pos</h2>
            <div class="mb-6 grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Inizio contratto" name="f.inizio_contratto"><input type="date" wire:model="f.inizio_contratto" class="input"></x-ui.field>
                <x-ui.field label="Fine contratto" name="f.fine_contratto"><input type="date" wire:model="f.fine_contratto" class="input"></x-ui.field>
                <x-ui.field label="Pos" name="f.pos" class="sm:col-span-2"><textarea wire:model="f.pos" rows="7" class="input" placeholder="Descrizione Pos"></textarea></x-ui.field>
            </div>
            <div class="grid gap-6 lg:grid-cols-3">
                <div>
                    <x-ui.field label="Tariffa oraria" name="f.tariffa">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">€</span>
                            <input wire:model="f.tariffa" inputmode="decimal" placeholder="0,00" class="input !pl-7 !pr-12">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">/ ora</span>
                        </div>
                    </x-ui.field>
                    <p class="mt-1 text-xs text-slate-500">Importo addebitato al cliente per ogni ora di servizio.</p>
                </div>
                <div class="lg:col-span-2">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="label !mb-0">Copertura settimanale</label>
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">Totale settimana: <span x-text="Object.values($wire.ore).reduce((t, v) => t + (parseFloat(String(v).replace(',', '.')) || 0), 0).toLocaleString('it-IT')"></span> h</span>
                    </div>
                    <p class="mb-3 text-xs text-slate-500">Indica quante ore di presenza sono richieste in ciascun giorno. Lascia vuoto o 0 per i giorni senza servizio.</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                        @foreach ($giorni as $n => $g)
                            <div class="rounded-xl p-3 text-center ring-1 {{ $n >= 6 ? 'bg-amber-50/60 ring-amber-100' : 'bg-sky-50/60 ring-sky-100' }}">
                                <span class="block text-xs font-bold uppercase tracking-wide text-slate-600">{{ $g }}</span>
                                <div class="relative mt-2">
                                    <input wire:model.live.debounce.300ms="ore.{{ $n }}" inputmode="decimal" placeholder="0" class="input !px-1 !pr-5 text-center">
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-slate-400">h</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('ore.*') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section x-show="tab === 'postazioni'" x-cloak class="space-y-4">
            @forelse ($postazioni as $chiave => $p)
                <div wire:key="post-{{ $chiave }}" class="card p-5 sm:p-6">
                    <div class="flex gap-4">
                        <div class="hidden w-40 shrink-0 sm:block">
                            @if (isset($immaginiPostazioni[$chiave]) && $immaginiPostazioni[$chiave])
                                <img src="{{ $immaginiPostazioni[$chiave]->temporaryUrl() }}" alt="" class="aspect-square w-full rounded-2xl object-cover ring-1 ring-sky-100">
                            @elseif ($p['id'] && $p['file'])
                                <img src="{{ route('postazioni.immagine', $p['id']) }}" alt="" class="aspect-square w-full rounded-2xl object-cover ring-1 ring-sky-100">
                            @else
                                <div class="flex aspect-square w-full items-center justify-center rounded-2xl bg-sky-50 text-sky-300 ring-1 ring-sky-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4-4a3 3 0 014 0l4 4m-2-2l1-1a3 3 0 014 0l2 2M14 8h.01M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                            @endif
                        </div>
                        <div class="grid min-w-0 flex-1 gap-4 sm:grid-cols-2">
                            <x-ui.field label="Qrcode associato" name="codice" class="sm:col-span-2"><input value="{{ $p['codice'] ?: 'Verrà generato al salvataggio' }}" class="input bg-slate-50 text-slate-500" readonly></x-ui.field>
                            <x-ui.field label="Titolo postazione *" name="postazioni.{{ $chiave }}.titolo" class="sm:col-span-2"><input wire:model="postazioni.{{ $chiave }}.titolo" class="input"></x-ui.field>
                            <x-ui.field label="Numero passaggi per turno" name="postazioni.{{ $chiave }}.passaggi"><input type="number" min="0" wire:model="postazioni.{{ $chiave }}.passaggi" class="input"></x-ui.field>
                            <x-ui.field label="Minuti di passaggio dopo inizio turno" name="postazioni.{{ $chiave }}.minuti"><input type="number" min="0" wire:model="postazioni.{{ $chiave }}.minuti" class="input"></x-ui.field>
                            <x-ui.field label="Info postazione" name="postazioni.{{ $chiave }}.info" class="sm:col-span-2"><textarea wire:model="postazioni.{{ $chiave }}.info" rows="4" class="input"></textarea></x-ui.field>
                            <x-ui.field label="Carica immagine" name="immaginiPostazioni.{{ $chiave }}" class="sm:col-span-2"><input type="file" wire:model="immaginiPostazioni.{{ $chiave }}" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-sky-700"></x-ui.field>
                        </div>
                        <button type="button" wire:click="rimuoviPostazione('{{ $chiave }}')" wire:confirm="Rimuovere la postazione? L'eliminazione avviene al salvataggio." class="btn-danger h-fit !px-3 !py-1.5" title="Rimuovi postazione">Rimuovi</button>
                    </div>
                </div>
            @empty
                <p class="card p-8 text-center text-sm text-slate-500">Nessuna postazione per questa sede.</p>
            @endforelse
            <button type="button" wire:click="aggiungiPostazione" class="btn-soft">+ Aggiungi postazione</button>
        </section>

        <div class="flex items-center justify-between gap-2">
            <p class="text-xs text-slate-500">I campi contrassegnati con * sono obbligatori.</p>
            <div class="flex gap-2">
                <a href="{{ route('sedi') }}" wire:navigate class="btn-soft">Annulla</a>
                <button class="btn-primary" wire:loading.attr="disabled" wire:target="foto,immaginiPostazioni"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $sedeId ? 'Aggiorna' : 'Inserisci' }}</button>
            </div>
        </div>
    </form>
</div>

@php
    $user = auth()->user();
    $ruolo = $user->ruolo->value;
    $menu = [
        'Amministrazione' => [
            ['dashboard', 'Dashboard', 'M3 3v7h7V3H3zm11 0v7h7V3h-7zM3 14v7h7v-7H3zm11 0v7h7v-7h-7z', ['super_admin', 'admin']],
            ['amministratori', 'Amministratori', 'M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m16 0v-2a4 4 0 00-3-3.87M12 7a4 4 0 11-8 0 4 4 0 018 0zm8 4a4 4 0 00-3-3.87', ['super_admin', 'admin']],
            ['accessi', 'Log accessi', 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z', ['super_admin', 'admin']],
        ],
        'Anagrafica' => [
            ['clienti', 'Clienti', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5', ['super_admin', 'admin']],
            ['operatori', 'Operatori', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', ['super_admin', 'admin']],
            ['sedi', 'Sedi', 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z', ['super_admin', 'admin', 'cliente']],
        ],
        'Turni e presenze' => [
            ['scansione', 'Timbra', 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', ['operatore']],
            ['miei-turni', 'I miei turni', 'M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z', ['operatore']],
            ['turni', 'Turni', 'M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z', ['super_admin', 'admin', 'cliente']],
            ['presenze', 'Presenze', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', ['super_admin', 'admin', 'cliente']],
        ],
        'Comunicazioni e documenti' => [
            ['bacheca', 'Bacheca', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', ['super_admin', 'admin', 'operatore']],
            ['messaggi', 'Messaggi', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', ['super_admin', 'admin']],
            ['messaggi.modelli', 'Modelli automatici', 'M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z', ['super_admin', 'admin']],
            ['documenti', 'Documenti', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', ['super_admin', 'admin', 'cliente', 'operatore']],
        ],
    ];
    $vociDisabilitate = ['sedi', 'turni', 'presenze', 'bacheca', 'messaggi', 'messaggi.modelli', 'documenti'];
    $paroleChiaveRicerca = [
        'dashboard' => 'dashboard panoramica riepilogo statistiche iniziale',
        'amministratori' => 'admin amministratori utenti account accessi',
        'accessi' => 'log accessi login audit utenti',
        'clienti' => 'aziende anagrafica clienti',
        'sedi' => 'sedi filiali indirizzi posizioni',
        'operatori' => 'personale guardie dipendenti operatori',
        'scansione' => 'qr codice timbratura timbra presenza',
        'miei-turni' => 'miei turni calendario lavoro',
        'turni' => 'turni calendario orari pianificazione',
        'presenze' => 'presenze timbrature rilevazioni report',
        'bacheca' => 'bacheca avvisi comunicazioni notizie',
        'messaggi' => 'messaggi email comunicazioni',
        'messaggi.modelli' => 'modelli automatici sms email notifiche',
        'documenti' => 'documenti file allegati scadenze',
    ];
    $ricercaVoci = collect($menu)
        ->flatMap(fn ($voci, $gruppo) => collect($voci)
            ->filter(fn ($voce) => in_array($ruolo, $voce[3], true) && ! in_array($voce[0], $vociDisabilitate, true))
            ->map(fn ($voce) => [
                'nome' => $voce[1],
                'gruppo' => $gruppo,
                'url' => route($voce[0]),
                'paroleChiave' => $paroleChiaveRicerca[$voce[0]] ?? '',
            ]))
        ->values()
        ->push(['nome' => 'Il mio profilo', 'gruppo' => 'Account', 'url' => route('profilo'), 'paroleChiave' => 'profilo account password foto nome cognome dati personali']);
    $logo = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
@endphp
<!DOCTYPE html>
<html lang="it" class="h-full bg-sky-50/60">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Security Scanner' }} · Security Scanner</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-700 antialiased" x-data="{
    open: window.innerWidth >= 1024 && localStorage.getItem('sidebar') !== '0',
    toggle() { this.open = !this.open; if (window.innerWidth >= 1024) localStorage.setItem('sidebar', this.open ? '1' : '0'); },
    chiudiMobile() { if (window.innerWidth < 1024) this.open = false; }
}">
    <div class="flex min-h-full">
        <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/30 backdrop-blur-sm lg:hidden" @click="open = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-sky-100 bg-white transition-transform duration-200"
               :class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center gap-3 px-6">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500 text-white shadow">{!! $logo !!}</span>
                <span class="text-lg font-bold tracking-tight text-slate-800">Security <span class="text-sky-500">Scanner</span></span>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach ($menu as $gruppo => $voci)
                    @php $vociVisibili = array_filter($voci, fn ($voce) => in_array($ruolo, $voce[3], true)); @endphp
                    @if ($vociVisibili)
                        <div class="px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-widest text-slate-400 first:pt-0">{{ $gruppo }}</div>
                        @foreach ($vociVisibili as [$route, $label, $icon, $ruoli])
                            @if (in_array($route, $vociDisabilitate, true))
                                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300" aria-disabled="true" title="Disponibile prossimamente">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                    {{ $label }}
                                    <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Presto</span>
                                </span>
                                @continue
                            @endif
                            <a href="{{ route($route) }}" wire:navigate @click="chiudiMobile()"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($route) ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-100' : 'text-slate-600 hover:bg-sky-50/70 hover:text-sky-700' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                {{ $label }}
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </nav>

        </aside>

        <div class="flex min-h-full min-w-0 flex-1 flex-col transition-[padding] duration-200" :class="open ? 'lg:pl-64' : 'lg:pl-0'">
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-sky-100 bg-white/80 px-4 backdrop-blur">
                <button type="button" @click="toggle()" class="rounded-lg p-2 text-slate-600 hover:bg-sky-50" :aria-label="open ? 'Chiudi menu' : 'Apri menu'" :aria-expanded="open">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="hidden whitespace-nowrap font-bold text-slate-800 sm:inline" x-show="!open || window.innerWidth < 1024">Security <span class="text-sky-500">Scanner</span></span>
                <div class="relative mx-auto min-w-0 w-full max-w-xl flex-1" x-data="navSearch(@js($ricercaVoci))" @keydown.window.meta.k.prevent="$refs.input.focus(); aperta = true" @keydown.window.ctrl.k.prevent="$refs.input.focus(); aperta = true" @click.outside="aperta = false">
                    <label for="ricerca-pagine" class="sr-only">Cerca sezioni</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg>
                        <input id="ricerca-pagine" x-ref="input" x-model="ricerca" @focus="aperta = true" @keydown.arrow-down.prevent="sposta(1)" @keydown.arrow-up.prevent="sposta(-1)" @keydown.enter.prevent="vaiAlRisultato()" @keydown.escape.prevent="aperta = false" type="search" aria-label="Cerca sezioni" aria-controls="risultati-ricerca-pagine" placeholder="Cerca sezioni… (⌘K)" class="input !py-2 !pl-9 sm:!pr-16">
                        <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded sm:block border border-slate-200 px-1.5 py-0.5 text-[10px] text-slate-400">⌘ K</kbd>
                    </div>
                    <div x-cloak x-show="aperta && ricerca.trim().length > 0" x-transition.opacity class="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-2xl bg-white p-2 shadow-xl ring-1 ring-sky-100">
                        <ul id="risultati-ricerca-pagine" class="max-h-80 overflow-y-auto">
                            <template x-for="(voce, i) in risultati()" :key="voce.url">
                                <li>
                                    <a :href="voce.url" wire:navigate @mouseenter="indice = i" @click="aperta = false" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition" :class="indice === i ? 'bg-sky-50 text-sky-700' : 'text-slate-700 hover:bg-sky-50/70'">
                                        <span class="font-medium" x-text="voce.nome"></span>
                                        <span class="ml-3 text-xs text-slate-400" x-text="voce.gruppo"></span>
                                    </a>
                                </li>
                            </template>
                            <li x-show="risultati().length === 0" class="px-3 py-3 text-sm text-slate-500">Nessuna sezione trovata.</li>
                        </ul>
                        <div class="border-t border-sky-50 px-3 pt-2 text-[11px] text-slate-400">↑ ↓ per navigare <span class="mx-1">·</span> Invio per aprire <span class="mx-1">·</span> Esc per chiudere</div>
                    </div>
                </div>

                <div class="relative shrink-0" x-data="{ menuAccount: false }" @click.outside="menuAccount = false" @keydown.escape.window="menuAccount = false">
                    <button type="button" @click="menuAccount = !menuAccount" :aria-expanded="menuAccount" aria-haspopup="true" aria-label="Menu account"
                            class="rounded-full transition hover:ring-2 hover:ring-sky-200 focus:outline-none focus:ring-2 focus:ring-sky-300">
                        <x-ui.avatar :user="$user" class="h-9 w-9 text-sm" />
                    </button>
                    <div x-cloak x-show="menuAccount" x-transition.opacity class="absolute right-0 top-full z-50 mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-2xl bg-white p-3 shadow-xl ring-1 ring-sky-100">
                        <div class="mb-3 flex items-center gap-3">
                            <x-ui.avatar :user="$user" class="h-10 w-10 text-sm" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ trim($user->name.' '.$user->cognome) }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->ruolo->label() }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $user->email }}</p>
                            </div>
                        </div>
                        <a href="{{ route('profilo') }}" wire:navigate @click="menuAccount = false" class="btn-soft mb-2 w-full">Il mio profilo</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-soft w-full">Esci</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @if (isset($title))
                    <h1 @class([
                        'mb-6 text-2xl font-bold tracking-tight text-slate-800',
                        'mx-auto w-full max-w-5xl' => $title === 'Impostazioni',
                    ])>{{ $title }}</h1>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>

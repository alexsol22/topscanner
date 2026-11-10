@php
    $user = auth()->user();
    $ruolo = $user->ruolo->value;
    $paginaPresenze = request()->routeIs('presenze', 'presenze.*');
    $vistaPresenze = match (request()->route()?->getName()) {
        'presenze.clienti' => 'clienti',
        'presenze.operatori' => 'operatori',
        default => request()->query('vista', 'gda'),
    };
    $titoloPagina = $paginaPresenze
        ? match ($vistaPresenze) {
            'clienti' => 'Conteggio orario clienti',
            'operatori' => 'Conteggio orario operatori',
            default => 'Giornale delle attività',
        }
        : ($title ?? 'Omnia Control');
    $menu = [
        'Home' => [
            ['home', 'Home', 'M3 10.5L12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6', ['super_admin', 'admin', 'cliente', 'operatore']],
        ],
        'Anagrafica' => [
            ['clienti', 'Clienti', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5', ['super_admin', 'admin']],
            ['operatori', 'Operatori', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', ['super_admin', 'admin']],
        ],
        'Documenti' => [
            ['documenti', 'Documenti', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', ['super_admin', 'admin', 'cliente', 'operatore']],
        ],
        'Comunicazioni' => [
            ['bacheca', 'Bacheca', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', ['super_admin', 'admin', 'operatore']],
        ],
        'Attività' => [
            ['turni', 'Agenda Pos', 'M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z', ['super_admin', 'admin', 'cliente']],
            ['miei-turni', 'Agenda Pos', 'M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z', ['operatore']],
        ],
        'Gda' => [
            ['gda-menu', 'Gda', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', ['super_admin', 'admin', 'cliente']],
        ],
        'Impostazioni' => [
            ['impostazioni-menu', 'Impostazioni', 'M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 0V4m0 16v-4m4-4h4M4 12h4m6.83-2.83l2.83-2.83m-11.32 11.32l2.83-2.83m5.66 0l2.83 2.83M6.17 6.17L9 9', ['super_admin', 'admin']],
        ],
    ];
    $sottomenu = [
        'gda-menu' => [
            ['presenze', 'Gda', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', ['super_admin', 'admin', 'cliente'], []],
            ['presenze.clienti', 'Orari clienti', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3', ['super_admin', 'admin'], []],
            ['presenze.operatori', 'Orari operatori', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1m0 0h6v-1a6 6 0 00-9-5.197', ['super_admin', 'admin'], []],
        ],
        'impostazioni-menu' => [
            ['messaggi', 'Messaggi', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A2 2 0 0120 9v10a2 2 0 01-2 2H7a2 2 0 01-2-2z', ['super_admin', 'admin'], []],
            ['messaggi.canale', 'SMS inviati', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v2', ['super_admin', 'admin'], ['canale' => 'sms']],
            ['messaggi.canale', 'Email inviate', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', ['super_admin', 'admin'], ['canale' => 'email']],
            ['amministratori', 'Utenti', 'M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m16 0v-2a4 4 0 00-3-3.87M12 7a4 4 0 11-8 0 4 4 0 018 0zm8 4a4 4 0 00-3-3.87', ['super_admin', 'admin'], []],
            ['whatsapp-inviati', 'WhatsApp inviati', 'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z', ['super_admin', 'admin'], []],
            // ['generatore-qr', 'Generatore QR', 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM19 14h2v7h-7v-2', ['super_admin', 'admin'], []],
        ],
    ];
    $vociDisabilitate = ['whatsapp-inviati'];
@endphp
<!DOCTYPE html>
<html lang="it" class="h-full bg-sky-50/60">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/omnia-control.svg') }}?v=2">
    <x-ui.tema-script />
    <title>{{ $titoloPagina }} · Omnia Control</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-700 antialiased" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="flex min-h-full min-w-0 flex-col">
        <header class="sticky top-0 z-40 border-b border-sky-100 bg-white/80 backdrop-blur">
            <div class="flex h-16 items-center gap-2 px-4 sm:gap-3 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2 font-bold text-slate-800">
                    <img src="{{ asset('brand/omnia-control.svg') }}?v=2" alt="" class="h-9 w-9 shrink-0">
                    <span class="truncate">Omnia <span class="text-[#b65d3b]">Control</span></span>
                </a>

                @if ($ruolo === 'operatore')
                    <livewire:campanella-notifiche />
                @endif

                <x-ui.tema-toggle class="shrink-0" />

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
                <button type="button" @click="open = !open" class="rounded-lg p-2 text-slate-600 hover:bg-sky-50 lg:hidden" :aria-label="open ? 'Chiudi menu' : 'Apri menu'" :aria-expanded="open" aria-controls="navigazione-principale">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            <nav id="navigazione-principale" aria-label="Navigazione principale" x-cloak x-show="open" class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-sky-100 px-3 py-2 lg:!flex lg:max-h-none lg:flex-wrap lg:items-center lg:gap-1 lg:overflow-visible lg:px-6 lg:py-1">
                @foreach ($menu as $gruppo => $voci)
                    @php $vociVisibili = array_filter($voci, fn ($voce) => in_array($ruolo, $voce[3], true)); @endphp
                    @if ($vociVisibili)
                        @foreach ($vociVisibili as [$route, $label, $icon, $ruoli])
                            @if (isset($sottomenu[$route]))
                                @php
                                    $sezioneSottomenuAttiva = fn (string $sottoRoute, array $parametri): bool => request()->routeIs($sottoRoute)
                                        && collect($parametri)->every(fn ($valore, $chiave) => (string) request()->query($chiave) === (string) $valore)
                                        && ($sottoRoute !== 'presenze' || request()->query('vista', 'gda') === 'gda');
                                    $sottomenuAttivo = collect($sottomenu[$route])->contains(fn ($voce) => in_array($ruolo, $voce[3], true) && $sezioneSottomenuAttiva($voce[0], $voce[4]));
                                @endphp
                                <div class="relative" x-data="{ aperto: false }" @click.outside="aperto = false" @keydown.escape.window="aperto = false">
                                    <button type="button" @click="aperto = !aperto" :aria-expanded="aperto"
                                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition hover:bg-sky-50/70 hover:text-sky-700 {{ $sottomenuAttivo ? 'text-sky-700' : 'text-slate-600' }}">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                        {{ $label }}
                                        <svg class="ml-auto h-4 w-4 transition-transform" :class="aperto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <ul x-cloak x-show="aperto" x-transition class="ml-5 mt-1 space-y-1 border-l border-sky-100 pl-2 lg:absolute lg:left-0 lg:top-full lg:z-50 lg:ml-0 lg:w-56 lg:rounded-2xl lg:border-0 lg:bg-white lg:p-2 lg:shadow-xl lg:ring-1 lg:ring-sky-100">
                                        @foreach ($sottomenu[$route] as [$sottoRoute, $sottoLabel, $sottoIcon, $sottoRuoli, $parametri])
                                            @if (! in_array($ruolo, $sottoRuoli, true))
                                                @continue
                                            @endif
                                            @if (in_array($sottoRoute, $vociDisabilitate, true))
                                                <li>
                                                    <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-300" aria-disabled="true" title="Disponibile prossimamente">
                                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sottoIcon }}"/></svg>
                                                        {{ $sottoLabel }}
                                                        <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Presto</span>
                                                    </span>
                                                </li>
                                                @continue
                                            @endif
                                            @php
                                                $urlSottomenu = route($sottoRoute, $parametri);
                                                $sottomenuAttivo = $sezioneSottomenuAttiva($sottoRoute, $parametri);
                                            @endphp
                                            <li>
                                                <a href="{{ $urlSottomenu }}" wire:navigate @click="open = false; aperto = false"
                                                   class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $sottomenuAttivo ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-100' : 'text-slate-600 hover:bg-sky-50/70 hover:text-sky-700' }}">
                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sottoIcon }}"/></svg>
                                                    {{ $sottoLabel }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @continue
                            @endif
                            @if (in_array($route, $vociDisabilitate, true))
                                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300" aria-disabled="true" title="Disponibile prossimamente">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                    {{ $label }}
                                    <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Presto</span>
                                </span>
                                @continue
                            @endif
                            <a href="{{ $route === 'home' ? url('/') : route($route) }}" wire:navigate @click="open = false"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $route !== 'home' && request()->routeIs($route) ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-100' : 'text-slate-600 hover:bg-sky-50/70 hover:text-sky-700' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                {{ $label }}
                                @if ($ruolo === 'operatore' && in_array($route, ['bacheca', 'documenti'], true))
                                    <livewire:contatore-notifiche :tipo="$route" :key="'badge-'.$route" />
                                @endif
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </nav>
        </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
                @if (isset($title) && ! $paginaPresenze && ! request()->routeIs('turni', 'miei-turni'))
                    <h1 @class([
                        'mb-6 text-2xl font-bold tracking-tight text-slate-800',
                        'mx-auto w-full max-w-5xl' => $titoloPagina === 'Impostazioni',
                    ])>{{ $titoloPagina }}</h1>
                @endif
                {{ $slot }}
            </main>
    </div>
    @livewireScripts
</body>
</html>

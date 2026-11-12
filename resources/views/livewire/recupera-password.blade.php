<main class="flex min-h-dvh flex-col items-center justify-center bg-slate-50 px-4 py-10">
    <div class="w-full max-w-sm">
        <a href="{{ route('login') }}" class="mb-8 flex items-center justify-center gap-3" aria-label="Omnia Control">
            <img src="{{ asset('brand/omnia-control.svg') }}?v=2" alt="" class="h-10 w-10">
            <span class="leading-none">
                <span class="block text-sm font-bold tracking-[0.2em] text-slate-800">OMNIA</span>
                <span class="mt-1 block text-[10px] font-semibold tracking-[0.28em] text-slate-500">CONTROL</span>
            </span>
        </a>

        <section class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-200 sm:p-8" aria-labelledby="recupero-title">
            <h1 id="recupero-title" class="text-xl font-semibold tracking-tight text-slate-900">Recupera password</h1>
            <p class="mt-1 text-sm text-slate-500">Inserisci il tuo utente: ti invieremo una nuova password via email.</p>

            @if ($inviata)
                <p class="mt-6 text-sm text-slate-600" role="status">Se l'utente esiste, abbiamo inoltrato al suo indirizzo email la nuova password. Ti consigliamo di cambiarla al primo accesso.</p>
            @else
                <form wire:submit="recupera" class="mt-6 space-y-4" novalidate>
                    @error('email')
                        <div role="alert" class="flex items-start gap-2.5 rounded-lg bg-rose-50 px-3.5 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-11.5a.9.9 0 01.9.9v3.2a.9.9 0 11-1.8 0V7.4a.9.9 0 01.9-.9zm0 7.6a1.1 1.1 0 100-2.2 1.1 1.1 0 000 2.2z" clip-rule="evenodd"/></svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Utente</label>
                        <input id="email" type="text" wire:model="email" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus
                               class="block w-full rounded-lg border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-600">
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="recupera">Invia nuova password</span>
                        <span wire:loading wire:target="recupera">Invio in corso…</span>
                    </button>
                </form>
            @endif

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" wire:navigate class="font-medium text-sky-700 hover:text-sky-800 hover:underline">Torna al login</a>
            </p>
        </section>

        <p class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-slate-500">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 10-9 0v3m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6.75a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z"/></svg>
            La nuova password arriva solo all'indirizzo associato all'utente
        </p>
    </div>
</main>

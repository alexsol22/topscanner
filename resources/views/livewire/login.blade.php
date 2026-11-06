<main class="flex min-h-dvh flex-col items-center justify-center bg-slate-50 px-4 py-10">
    <div class="w-full max-w-sm">
        <a href="{{ url('/') }}" class="mb-8 flex items-center justify-center gap-3" aria-label="Omnia Control">
            <img src="{{ asset('brand/omnia-control.svg') }}?v=2" alt="" class="h-10 w-10">
            <span class="leading-none">
                <span class="block text-sm font-bold tracking-[0.2em] text-slate-800">OMNIA</span>
                <span class="mt-1 block text-[10px] font-semibold tracking-[0.28em] text-slate-500">CONTROL</span>
            </span>
        </a>

        <section class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-200 sm:p-8" aria-labelledby="login-title">
            <h1 id="login-title" class="text-xl font-semibold tracking-tight text-slate-900">Accedi</h1>
            <p class="mt-1 text-sm text-slate-500">Inserisci le tue credenziali per continuare.</p>

            <form wire:submit="accedi" class="mt-6 space-y-4" novalidate>
                @error('email')
                    <div role="alert" class="flex items-start gap-2.5 rounded-lg bg-rose-50 px-3.5 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-11.5a.9.9 0 01.9.9v3.2a.9.9 0 11-1.8 0V7.4a.9.9 0 01.9-.9zm0 7.6a1.1 1.1 0 100-2.2 1.1 1.1 0 000 2.2z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <div>
                    <label for="utente" class="mb-1.5 block text-sm font-medium text-slate-700">Utente</label>
                    <input id="utente" type="text" wire:model="email" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus
                           class="block w-full rounded-lg border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-600">
                </div>

                <div x-data="{ visibile: false }">
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        <a href="{{ route('password.recupero') }}" wire:navigate class="text-xs font-medium text-sky-700 hover:text-sky-800 hover:underline">Password dimenticata?</a>
                    </div>
                    <div class="relative">
                        <input id="password" :type="visibile ? 'text' : 'password'" wire:model="password" autocomplete="current-password"
                               class="block w-full rounded-lg border-0 bg-white py-2.5 pl-3.5 pr-11 text-sm text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-600">
                        <button type="button" @click="visibile = !visibile" :aria-pressed="visibile" :aria-label="visibile ? 'Nascondi password' : 'Mostra password'"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600">
                            <svg x-show="!visibile" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 010 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg x-show="visibile" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 001.93 12c1.29 4.17 5.2 7.2 9.82 7.2 1.9 0 3.69-.5 5.23-1.4M6.23 6.23A10.45 10.45 0 0112 4.8c4.62 0 8.53 3.03 9.82 7.2a10.5 10.5 0 01-4.29 5.77M6.23 6.23L3 3m3.23 3.23l3.65 3.65m7.89 7.89L21 21m-3.23-3.23l-3.65-3.65m0 0a3 3 0 10-4.24-4.24m4.24 4.24L9.88 9.88"/></svg>
                        </button>
                    </div>
                    @error('password') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="accedi">Accedi</span>
                    <span wire:loading wire:target="accedi">Accesso in corso…</span>
                </button>
            </form>
        </section>

        <p class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-slate-500">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 10-9 0v3m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6.75a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z"/></svg>
            Accesso riservato al personale autorizzato
        </p>
    </div>
</main>

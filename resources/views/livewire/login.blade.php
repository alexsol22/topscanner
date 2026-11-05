<div class="flex min-h-full items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-500 text-white shadow-lg shadow-sky-200">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </span>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-800">Omnia <span class="text-sky-500">Control</span></h1>
            <p class="mt-1 text-sm text-slate-500">Gestione servizi di vigilanza e presenze</p>
        </div>
        <form wire:submit="accedi" class="card space-y-5 p-6 sm:p-8">
            <div>
                <label class="label" for="email">Utente</label>
                <input id="email" type="text" wire:model="email" class="input" autocomplete="username" autofocus>
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" type="password" wire:model="password" class="input" autocomplete="current-password">
                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="remember" class="rounded border-sky-300 text-sky-500 focus:ring-sky-300"> Ricordami
            </label>
            <button class="btn-primary w-full" wire:loading.attr="disabled">Accedi</button>
            <p class="text-center text-sm"><a href="{{ route('password.recupero') }}" wire:navigate class="text-sky-600 hover:underline">Password dimenticata?</a></p>
        </form>
    </div>
</div>

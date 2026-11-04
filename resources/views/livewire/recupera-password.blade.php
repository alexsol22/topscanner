<div class="flex min-h-full items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-800">Recupero <span class="text-sky-500">password</span></h1>
            <p class="mt-1 text-sm text-slate-500">Inserisci il tuo utente (email): ti invieremo una nuova password.</p>
        </div>
        <div class="card space-y-5 p-6 sm:p-8">
            @if ($inviata)
                <p class="text-sm text-slate-600">Se l'utente esiste, abbiamo inoltrato al suo indirizzo email la nuova password. Ti consigliamo di cambiarla al primo accesso.</p>
            @else
                <form wire:submit="recupera" class="space-y-5">
                    <div>
                        <label class="label" for="email">Utente / Email</label>
                        <input id="email" type="text" wire:model="email" class="input" autocomplete="username" autofocus>
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <button class="btn-primary w-full" wire:loading.attr="disabled">Recupera</button>
                </form>
            @endif
            <p class="text-center text-sm text-slate-500">Torna al <a href="{{ route('login') }}" wire:navigate class="text-sky-600 hover:underline">login</a></p>
        </div>
    </div>
</div>

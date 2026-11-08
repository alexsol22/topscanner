@props(['values', 'title' => 'Filtri', 'triggerLabel' => 'Filtri', 'action' => null])

<div x-data="{
    aperta: false,
    invio: false,
    errore: '',
    azione: @js($action),
    nomi: @js(array_keys($values)),
    valori: {},
    get attivi() {
        return this.nomi.filter(nome => {
            const valore = $wire.$get(nome);
            return valore !== null && valore !== '';
        }).length;
    },
    apri() {
        this.valori = Object.fromEntries(this.nomi.map(nome => [nome, $wire.$get(nome)]));
        this.errore = '';
        this.aperta = true;
    },
    async applica() {
        if (this.invio) return;
        this.invio = true;
        this.errore = '';
        const precedenti = Object.fromEntries(Object.keys(this.valori).map(nome => [nome, $wire.$get(nome)]));
        try {
            if (this.azione) {
                await $wire.$call(this.azione, JSON.parse(JSON.stringify(this.valori)));
            } else {
                for (const [nome, valore] of Object.entries(this.valori)) {
                    await $wire.$set(nome, valore, false);
                }
                await $wire.$commit();
            }
            this.aperta = false;
            this.$nextTick(() => this.$refs.apri.focus());
        } catch (errore) {
            if (!this.azione) {
                for (const [nome, valore] of Object.entries(precedenti)) {
                    await $wire.$set(nome, valore, false);
                }
            }
            this.errore = 'Impossibile applicare i filtri. Controlla i valori e riprova.';
            console.error('Errore durante l’applicazione dei filtri:', errore);
        } finally {
            this.invio = false;
        }
    },
    chiudi() {
        if (this.invio) return;
        this.aperta = false;
        this.$nextTick(() => this.$refs.apri.focus());
    },
    azzera() {
        for (const nome of Object.keys(this.valori)) {
            this.valori[nome] = typeof this.valori[nome] === 'number' || this.valori[nome] === null ? null : '';
        }
    }
}" class="inline-flex">
        <button x-ref="apri" type="button" @click="apri()" class="btn-soft" aria-label="{{ $triggerLabel }}" title="{{ $triggerLabel }}" :aria-expanded="aperta">
            @isset($trigger)
                {{ $trigger }}
            @else
                {{ $triggerLabel }}
            @endisset
            <span x-cloak x-show="attivi" x-text="attivi" class="rounded-full bg-sky-100 px-2 py-0.5 text-xs" aria-hidden="true"></span>
            <span x-cloak x-show="attivi" class="sr-only" x-text="`${attivi} filtri attivi`"></span>
        </button>
        <div x-cloak x-show="aperta" x-trap.noscroll="aperta" @keydown.escape.stop="chiudi()" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center sm:p-4">
            <div role="dialog" aria-modal="true" aria-label="{{ $title }}" @mousedown.outside="chiudi()" class="max-h-[90dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-3xl sm:rounded-3xl sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ $title }}</h2>
                    <button type="button" @click="chiudi()" aria-label="Chiudi filtri" class="rounded-lg p-2 text-slate-500 hover:bg-sky-50">✕</button>
                </div>
                <form @submit.prevent="applica()">
                    {{ $slot }}
                    <p x-cloak x-show="errore" x-text="errore" role="alert" class="mt-4 text-sm text-rose-600"></p>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button type="button" @click="azzera()" :disabled="invio" class="btn-soft">Azzera filtri</button>
                        <button type="button" @click="chiudi()" :disabled="invio" class="btn-soft">Annulla</button>
                        <button type="submit" :disabled="invio" class="btn-primary">Applica filtri</button>
                    </div>
                </form>
            </div>
        </div>
</div>

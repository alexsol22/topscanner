<button type="button"
        x-data="{ scuro: document.documentElement.classList.contains('dark') }"
        x-init="document.addEventListener('livewire:navigated', () => scuro = document.documentElement.classList.contains('dark'))"
        @click="scuro = !scuro; document.documentElement.classList.toggle('dark', scuro); localStorage.setItem('tema', scuro ? 'scuro' : 'chiaro')"
        :aria-label="scuro ? 'Attiva tema chiaro' : 'Attiva tema scuro'" :title="scuro ? 'Tema chiaro' : 'Tema scuro'"
        {{ $attributes->merge(['class' => 'rounded-lg p-2 text-slate-600 transition hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-300']) }}>
    <svg x-show="!scuro" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    <svg x-show="scuro" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"/></svg>
</button>

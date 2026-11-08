@props(['user', 'preview' => null])
@php
    $src = null;
    if ($preview && method_exists($preview, 'temporaryUrl')) {
        try {
            $src = $preview->temporaryUrl();
        } catch (\Throwable) {
            $src = null;
        }
    }
    $src ??= route('profilo.foto', [$user, 'v' => $user->foto_path ? md5($user->foto_path) : 'default']);
@endphp
<span x-data="{ fotoDisponibile: true }" {{ $attributes->class('relative flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100 font-bold text-sky-700') }}>
    <img x-show="fotoDisponibile" x-on:error="fotoDisponibile = false" src="{{ $src }}" alt="Foto di {{ $user->nome_completo }}" class="h-full w-full object-cover">
    <svg x-cloak x-show="!fotoDisponibile" xmlns="http://www.w3.org/2000/svg" class="h-1/2 w-1/2 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0ZM4.5 20.25a7.5 7.5 0 0115 0" />
    </svg>
</span>

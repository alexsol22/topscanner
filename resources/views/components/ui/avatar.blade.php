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
<span {{ $attributes->class('flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100 font-bold text-sky-700') }}>
    <img src="{{ $src }}" alt="Foto di {{ $user->nome_completo }}" class="h-full w-full object-cover">
</span>

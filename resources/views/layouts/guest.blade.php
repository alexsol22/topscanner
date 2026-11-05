<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-ui.tema-script />
    <title>Accedi · Omnia Control</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gradient-to-br from-sky-50 via-white to-sky-100 font-sans text-slate-700 antialiased">
    <x-ui.tema-toggle class="fixed right-4 top-4 z-10" />
    {{ $slot }}
    @livewireScripts
</body>
</html>

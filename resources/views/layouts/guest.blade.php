<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/omnia-control.svg') }}?v=2">
    <title>Accedi · Omnia Control</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-[#f1f8fb] font-sans text-slate-700 antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>

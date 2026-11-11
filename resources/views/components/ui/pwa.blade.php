<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#f4f9fc">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Omnia">
<link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(@json(asset('sw.js')));
    }
</script>

<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\SmsConsegnaController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Livewire;
use App\Models\Legacy\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', function () {
    $manifest = [
        'id' => '/',
        'name' => 'Omnia Control',
        'short_name' => 'Omnia',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#152B38',
        'theme_color' => '#f4f9fc',
        'lang' => 'it',
        'icons' => [
            [
                'src' => '/brand/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/brand/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/brand/icon-maskable-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ];

    return response(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
})->name('pwa.manifest');

Route::get('/login', Livewire\Login::class)->name('login')->middleware('guest');
Route::match(['get', 'post'], '/sms/consegna', SmsConsegnaController::class)->name('sms.consegna');
Route::match(['get', 'post'], '/whatsapp/webhook', WhatsAppWebhookController::class)->name('whatsapp.webhook');
Route::get('/recupera-password', Livewire\RecuperaPassword::class)->name('password.recupero')->middleware('guest');

Route::post('/logout', function () {
    if (Auth::check()) {
        Tracking::registra('Uscita utente ');
    }

    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $u = auth()->user();

        return redirect()->route(match (true) {
            $u->isStaff() => 'clienti',
            $u->isOperatore() => 'scansione',
            default => 'turni',
        });
    });
    Route::get('/profilo', Livewire\Profilo::class)->name('profilo');
    Route::get('/profilo/foto/{user}', [DownloadController::class, 'fotoProfilo'])->name('profilo.foto');
    Route::get('/bacheca', Livewire\Bacheca::class)->name('bacheca');
    Route::get('/documenti', Livewire\Documenti::class)->name('documenti');
    Route::get('/documenti/{documento}/scarica', [DownloadController::class, 'documento'])->name('documenti.scarica');
    Route::get('/collaudo-manuale', function () {
        abort_unless(auth()->user()->email === 'alessiosolari22@gmail.com', 403);

        return response()->view('collaudo-manuale');
    })->name('collaudo-manuale');

    Route::middleware('ruolo:operatore')->group(function () {
        Route::get('/scansione', Livewire\Scansione::class)->name('scansione');
        Route::get('/scansione/foto', [DownloadController::class, 'fotoTurnoAttivo'])->name('scansione.foto');
    });

    Route::middleware('ruolo:super_admin,admin,cliente')->group(function () {
        Route::get('/sedi', Livewire\Sedi::class)->name('sedi');
        Route::get('/sedi/nuova', Livewire\SedeForm::class)->name('sedi.crea');
        Route::get('/sedi/{sede}/modifica', Livewire\SedeForm::class)->name('sedi.modifica');
        Route::get('/sedi/{sede}/foto', [DownloadController::class, 'fotoSede'])->name('sedi.foto');
        Route::get('/sedi/{sede}/qrcode', [DownloadController::class, 'qrSede'])->name('sedi.qrcode');
        Route::get('/postazioni/{postazione}/immagine', [DownloadController::class, 'immaginePostazione'])->name('postazioni.immagine');
        Route::get('/postazioni/{postazione}/qrcode', [DownloadController::class, 'qrPostazione'])->name('postazioni.qrcode');
        Route::get('/turni', Livewire\Turni::class)->name('turni');
        Route::get('/presenze', Livewire\Presenze::class)->name('presenze');
        Route::get('/presenze/clienti', Livewire\Presenze::class)->name('presenze.clienti');
        Route::get('/presenze/operatori', Livewire\Presenze::class)->name('presenze.operatori');
        Route::get('/presenze/stampa', [DownloadController::class, 'stampaGda'])->name('presenze.stampa');
        Route::get('/presenze/esporta', [DownloadController::class, 'presenze'])->name('presenze.esporta');
        Route::get('/presenze/ore/{tipo}/{id}', [DownloadController::class, 'oreTurni'])->whereIn('tipo', ['cliente', 'operatore'])->whereNumber('id')->name('presenze.ore');
    });

    Route::middleware('ruolo:operatore')->get('/miei-turni', Livewire\Turni::class)->name('miei-turni');

    Route::middleware('ruolo:super_admin,admin')->group(function () {
        Route::get('/clienti', Livewire\Clienti::class)->name('clienti');
        Route::get('/operatori', Livewire\Operatori::class)->name('operatori');
        Route::get('/messaggi', Livewire\ModelliMessaggio::class)->name('messaggi');
        Route::get('/generatore-qr', Livewire\GeneratoreQr::class)->name('generatore-qr');
        Route::get('/codici/{codice}/qrcode', [DownloadController::class, 'qrCodice'])->name('codici.qrcode');
        Route::get('/messaggi/modelli', fn () => redirect()->route('messaggi'))->name('messaggi.modelli');
        Route::get('/messaggi/{canale}', Livewire\Messaggi::class)
            ->whereIn('canale', ['sms', 'email', 'whatsapp'])
            ->name('messaggi.canale');
        Route::get('/impostazioni', Livewire\Impostazioni::class)->name('impostazioni');
    });

    Route::middleware('ruolo:super_admin,admin')->group(function () {
        Route::get('/amministratori', Livewire\Amministratori::class)->name('amministratori');
        Route::get('/accessi', Livewire\Accessi::class)->name('accessi');
    });
});

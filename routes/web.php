<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\SmsConsegnaController;
use App\Livewire;
use App\Models\Legacy\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', Livewire\Login::class)->name('login')->middleware('guest');
Route::match(['get', 'post'], '/sms/consegna', SmsConsegnaController::class)->name('sms.consegna');
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

    Route::middleware('ruolo:operatore')->group(function () {
        Route::get('/scansione', Livewire\Scansione::class)->name('scansione');
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
        Route::get('/messaggi/modelli', fn () => redirect()->route('messaggi'))->name('messaggi.modelli');
        Route::get('/messaggi/{canale}', Livewire\Messaggi::class)
            ->whereIn('canale', ['sms', 'email'])
            ->name('messaggi.canale');
        Route::get('/impostazioni', Livewire\Impostazioni::class)->name('impostazioni');
    });

    Route::middleware('ruolo:super_admin,admin')->group(function () {
        Route::get('/amministratori', Livewire\Amministratori::class)->name('amministratori');
        Route::get('/accessi', Livewire\Accessi::class)->name('accessi');
    });
});

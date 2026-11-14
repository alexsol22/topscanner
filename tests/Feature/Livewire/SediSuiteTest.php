<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GeneratoreQr;
use App\Livewire\SedeForm;
use App\Livewire\Sedi;
use App\Models\User;
use App\Services\QrSede;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SediSuiteTest extends TestCase
{
    private string $disco;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        $this->disco = config('filesystems.files_disk');
        Storage::fake($this->disco);
        $this->creaSchema();
    }

    private function utente(int $tipo, int $id = 900, ?int $idCliente = null): User
    {
        DB::table('tc_admin')->insertOrIgnore(['id' => $id, 'user_type' => $tipo, 'attivo' => 'Y', 'nome' => 'Test', 'cognome' => "T$id", 'id_cliente' => $idCliente]);

        return User::find($id);
    }

    private function admin(): User
    {
        return $this->utente(2, 900);
    }

    private function cliente(string $nome = 'Alpha Srl', ?int $isTest = null): int
    {
        return DB::table('tc_clienti')->insertGetId(['ragione_sociale' => $nome, 'isTest' => $isTest]);
    }

    /** @return array{sede: int, postazione: int, codiceSede: int, codicePostazione: int} */
    private function sedeCompleta(int $cliente, string $nome = 'Sede Uno'): array
    {
        $sede = DB::table('tc_sedi')->insertGetId(['id_cliente' => $cliente, 'descrizione' => $nome, 'lat' => 44.1, 'lng' => 10.1]);
        $codiceSede = DB::table('tc_codici')->insertGetId(['codice' => "ITALPOL*S$sede", 'stato' => 'A', 'tipo' => 'S', 'id_sede' => $sede]);
        $postazione = DB::table('tc_postazioni')->insertGetId(['id_cliente' => $cliente, 'id_sede' => $sede, 'titolo' => 'Ingresso']);
        $codicePostazione = DB::table('tc_codici')->insertGetId(['codice' => "ITALPOL*P$postazione", 'stato' => 'A', 'tipo' => 'P', 'id_sede' => $sede, 'id_postazione' => $postazione]);
        DB::table('tc_sedi')->where('id', $sede)->update(['id_codice' => $codiceSede]);
        DB::table('tc_postazioni')->where('id', $postazione)->update(['id_codice' => $codicePostazione]);
        Storage::disk($this->disco)->put("protected/documenti/Cliente_$cliente/Sede_$sede/foto.jpg", 'x');
        Storage::disk($this->disco)->put("protected/documenti/Cliente_$cliente/Sede_$sede/Postazione_$postazione/p.jpg", 'x');

        return compact('sede', 'postazione', 'codiceSede', 'codicePostazione');
    }

    /** @return array{0: Testable, 1: string, 2: int} */
    private function formConPostazione(): array
    {
        $cliente = $this->cliente();
        $codiceSede = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000401', 'stato' => 'C']);
        $codicePostazione = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000402', 'stato' => 'C']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        $form = Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', (string) $codiceSede)
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede valida')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9')
            ->call('aggiungiPostazione');

        return [$form, array_key_first($form->get('postazioni')), $codicePostazione];
    }

    public function test_delete_requires_exact_confirmation_word(): void
    {
        $c = $this->cliente();
        $s = $this->sedeCompleta($c);

        Livewire::actingAs($this->admin())->test(Sedi::class)
            ->call('richiediEliminazione', $s['sede'])->assertSet('eliminaId', $s['sede'])
            ->set('conferma', 'sbagliato')->call('elimina')->assertHasErrors(['conferma']);

        $this->assertSame(1, DB::table('tc_sedi')->count());
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$c/Sede_{$s['sede']}/foto.jpg");
    }

    public function test_delete_cascade_is_complete_isolated_and_cleans_files_and_codes(): void
    {
        $c = $this->cliente();
        $altroCliente = $this->cliente('Beta');
        $a = $this->sedeCompleta($c, 'Da eliminare');
        $b = $this->sedeCompleta($altroCliente, 'Da tenere');
        foreach ([$a['sede'], $b['sede']] as $id) {
            DB::table('tc_turni')->insert(['id_sede' => $id, 'id_cliente' => $c, 'stato' => 'F']);
            DB::table('tc_turni_step')->insert(['id_sede' => $id]);
            DB::table('tc_documenti')->insert(['id_tipo' => $id, 'tipo' => 'S', 'file' => 'd.pdf']);
            DB::table('tc_gda')->insert(['id_sede' => $id]);
        }
        // Il bug del vecchio: l'id della sede usato come id_cliente non deve toccare altri clienti.
        DB::table('tc_postazioni')->insert(['id_cliente' => $a['sede'], 'id_sede' => $b['sede'], 'titolo' => 'Non toccare']);

        Livewire::actingAs($this->admin())->test(Sedi::class)
            ->call('richiediEliminazione', $a['sede'])->set('conferma', ' CONFERMA ')->call('elimina')
            ->assertHasNoErrors()->assertSet('eliminaId', null);

        $this->assertSame(0, DB::table('tc_sedi')->where('id', $a['sede'])->count());
        foreach (['tc_turni', 'tc_turni_step', 'tc_postazioni'] as $t) {
            $this->assertSame(0, DB::table($t)->where('id_sede', $a['sede'])->count(), $t);
            $this->assertGreaterThan(0, DB::table($t)->where('id_sede', $b['sede'])->count(), $t);
        }
        $this->assertSame(0, DB::table('tc_documenti')->where('id_tipo', $a['sede'])->where('tipo', 'S')->count());
        $this->assertSame(1, DB::table('tc_documenti')->where('id_tipo', $b['sede'])->count());
        $this->assertSame(2, DB::table('tc_gda')->count(), 'le presenze restano nello storico');
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$c/Sede_{$a['sede']}/foto.jpg");
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$c/Sede_{$a['sede']}/Postazione_{$a['postazione']}/p.jpg");
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$altroCliente/Sede_{$b['sede']}/foto.jpg");

        foreach ([$a['codiceSede'], $a['codicePostazione']] as $codice) {
            $riga = DB::table('tc_codici')->find($codice);
            $this->assertSame('C', $riga->stato);
            $this->assertNull($riga->id_sede);
        }
        $this->assertSame('A', DB::table('tc_codici')->find($b['codiceSede'])->stato);
        $this->assertSame(1, DB::table('_tracking')->where('action', 'like', 'Rimozione sede Da eliminare')->count());
    }

    public function test_delete_postazione_removes_folder_and_frees_code(): void
    {
        $c = $this->cliente();
        $s = $this->sedeCompleta($c);

        Livewire::actingAs($this->admin())->test(Sedi::class)->call('eliminaPostazione', $s['postazione']);

        $this->assertSame(0, DB::table('tc_postazioni')->count());
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$c/Sede_{$s['sede']}/Postazione_{$s['postazione']}/p.jpg");
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$c/Sede_{$s['sede']}/foto.jpg");
        $this->assertSame('C', DB::table('tc_codici')->find($s['codicePostazione'])->stato);
        $this->assertSame('A', DB::table('tc_codici')->find($s['codiceSede'])->stato);
    }

    public function test_list_shows_legacy_actions_without_technical_count_badges(): void
    {
        $c = $this->cliente();
        $s = $this->sedeCompleta($c);
        DB::table('_provincia')->insert(['id' => 1, 'sigla' => 'MO']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Mirandola', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);
        DB::table('tc_sedi')->where('id', $s['sede'])->update([
            'indirizzo' => 'Via Agnini,102',
            'cap' => '41037',
            'citta_id' => 1,
            'provincia_id' => 1,
        ]);
        DB::table('tc_turni')->insert([['id_sede' => $s['sede'], 'stato' => 'F'], ['id_sede' => $s['sede'], 'stato' => 'A'], ['id_sede' => $s['sede'], 'stato' => 'F']]);

        Livewire::actingAs($this->admin())->test(Sedi::class)->set('filtroCliente', $c)
            ->assertSee('Visualizza agenda pos')
            ->assertSee('href="'.route('turni', ['sede' => $s['sede']]).'"', false)
            ->assertSee('Visualizza GDA')
            ->assertSee('href="'.route('presenze', ['sede' => $s['sede']]).'"', false)
            ->assertSee('Scarica QR')
            ->assertSee('Modifica sede')
            ->assertSee('Elimina sede')
            ->assertDontSee('3 turni')
            ->assertDontSee('postazioni</span>')
            ->call('postazioni', $s['sede'])
            ->assertSee('Qrcode Sede Uno')
            ->assertSee('Postazione principale')
            ->assertSee('VIA AGNINI,102 41037 MIRANDOLA (MO)')
            ->assertSee('href="'.route('sedi.qrcode', $s['sede']).'"', false)
            ->assertSee('href="'.route('postazioni.qrcode', $s['postazione']).'"', false)
            ->assertDontSee('data:image/svg+xml')
            ->assertDontSee('Nuova postazione')
            ->assertDontSee('Ogni (min)')
            ->assertDontSee('ogni ')
            ->assertDontSee('Aggiungi postazione');
    }

    public function test_qr_downloads_serve_existing_files_and_enforce_customer_visibility(): void
    {
        $cliente = $this->cliente();
        $altroCliente = $this->cliente('Altro');
        $sede = $this->sedeCompleta($cliente);
        $jpeg = "\xff\xd8\xff".'contenuto legacy'."\xff\xd9";
        $percorsoSede = 'protected/documenti/QrcodeText/ITALPOL*S'.$sede['sede'].'.png';
        $percorsoPostazione = 'protected/documenti/QrcodeText/ITALPOL*P'.$sede['postazione'].'.png';
        Storage::disk($this->disco)->put($percorsoSede, $jpeg);
        Storage::disk($this->disco)->put($percorsoPostazione, $jpeg);

        $this->get(route('sedi.qrcode', $sede['sede']))->assertRedirect(route('login'));
        $this->get(route('postazioni.qrcode', $sede['postazione']))->assertRedirect(route('login'));

        foreach ([
            ['sedi.qrcode', $sede['sede'], basename($percorsoSede)],
            ['postazioni.qrcode', $sede['postazione'], basename($percorsoPostazione)],
        ] as [$nome, $id, $filename]) {
            $url = route($nome, $id);
            $this->actingAs($this->utente(3, 901, $cliente))->get($url)
                ->assertOk()->assertDownload($filename)->assertHeader('Content-Type', 'application/octet-stream')
                ->assertStreamedContent($jpeg);
            $this->actingAs($this->utente(3, 902, $altroCliente))->get($url)->assertForbidden();
        }

        Storage::disk($this->disco)->delete($percorsoSede);
        Storage::disk($this->disco)->delete($percorsoPostazione);
        $this->actingAs($this->admin())->get(route('sedi.qrcode', $sede['sede']))->assertNotFound();
        $this->actingAs($this->admin())->get(route('postazioni.qrcode', $sede['postazione']))->assertNotFound();
    }

    public function test_qr_generator_shows_the_next_code_and_hides_personalization_until_requested(): void
    {
        DB::table('tc_codici')->insert(['codice' => 'ITALPOL*000300', 'stato' => 'C']);

        $this->actingAs($this->admin())->get(route('generatore-qr'))
            ->assertOk()
            ->assertSee('class="mb-6 text-2xl font-bold tracking-tight text-slate-800 mx-auto w-full max-w-6xl"', false)
            ->assertSee('ITALPOL*000301')
            ->assertSee('Oggi ne restano 200')
            ->assertSee('Personalizza i codici')
            ->assertSee('role="switch"', false)
            ->assertDontSee('Codice libero senza foto')
            ->assertDontSee('Colore del codice QR');

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->assertSee('ITALPOL*000301')
            ->assertSee('Numero di codici')
            ->assertDontSee('Nuovo codice')
            ->assertDontSee('Codice libero senza foto')
            ->assertDontSee('Cerca un codice')
            ->set('personalizza', true)
            ->assertSee('Colore del codice QR')
            ->assertSee('Titolo sotto il QR')
            ->assertSee('Logo al centro')
            ->set('titolo', 'Portineria')
            ->assertSee('Portineria');
    }

    public function test_qr_generator_keeps_codes_plain_unless_personalization_is_enabled(): void
    {
        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('titolo', 'Portineria')
            ->set('colore', '#112233')
            ->set('quantita', 1)
            ->call('generaLiberi')
            ->assertHasNoErrors()
            ->assertSee('Creato ITALPOL*000001.');

        $qr = app(QrSede::class);
        $qr->genera('ITALPOL*000002');
        $generata = getimagesizefromstring(Storage::disk($this->disco)->get('protected/documenti/QrcodeText/ITALPOL*000001.png'));
        $piatta = getimagesizefromstring(Storage::disk($this->disco)->get($qr->percorso('ITALPOL*000002')));

        $this->assertSame($piatta[1], $generata[1]);
    }

    public function test_qr_generator_is_not_available_to_non_staff_users(): void
    {
        $operatore = $this->utente(4, 901);

        $this->actingAs($operatore)->get(route('generatore-qr'))->assertForbidden();
        Livewire::actingAs($operatore)->test(GeneratoreQr::class)->assertForbidden();
    }

    public function test_qr_generator_creates_the_next_numbers_and_the_site_form_lists_them(): void
    {
        DB::table('tc_codici')->insert([
            ['codice' => 'ITALPOL*000299', 'stato' => 'A', 'tipo' => 'S'],
            ['codice' => 'ITALPOL*S12', 'stato' => 'A', 'tipo' => 'S'],
        ]);

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('personalizza', true)
            ->set('quantita', 2)
            ->set('titolo', 'Portineria')
            ->set('colore', '#112233')
            ->set('logo', UploadedFile::fake()->image('logo.png', 32, 32))
            ->call('generaLiberi')
            ->assertHasNoErrors()
            ->assertSee('Creati da ITALPOL*000300 a ITALPOL*000301.')
            ->assertSee('ITALPOL*000302');

        $codici = DB::table('tc_codici')->whereIn('codice', ['ITALPOL*000300', 'ITALPOL*000301'])->orderBy('codice')->get();
        $this->assertSame(['ITALPOL*000300', 'ITALPOL*000301'], $codici->pluck('codice')->all());

        foreach ($codici as $codice) {
            $this->assertSame('C', $codice->stato);
            $this->assertNull($codice->tipo);
            $this->assertNull($codice->id_sede);
            $this->assertNull($codice->id_postazione);
            $percorso = 'protected/documenti/QrcodeText/'.$codice->codice.'.png';
            Storage::disk($this->disco)->assertExists($percorso);
            $this->assertSame('image/png', getimagesizefromstring(Storage::disk($this->disco)->get($percorso))['mime']);
        }

        $qr = app(QrSede::class);
        $qr->genera('ITALPOL*000302');
        $conTitolo = getimagesizefromstring(Storage::disk($this->disco)->get('protected/documenti/QrcodeText/ITALPOL*000300.png'));
        $senzaTitolo = getimagesizefromstring(Storage::disk($this->disco)->get($qr->percorso('ITALPOL*000302')));
        $this->assertGreaterThan($senzaTitolo[1], $conTitolo[1]);

        $this->actingAs($this->admin())->get(route('codici.qrcode', $codici[0]->id))
            ->assertOk()
            ->assertDownload('ITALPOL*000300.png');

        Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->assertSeeInOrder(['ITALPOL*000300', 'ITALPOL*000301'])
            ->assertDontSee('ITALPOL*000299')
            ->assertDontSee('ITALPOL*000302');
    }

    public function test_qr_generator_rejects_a_title_longer_than_forty_characters(): void
    {
        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('personalizza', true)
            ->set('titolo', str_repeat('a', 41))
            ->call('generaLiberi')
            ->assertHasErrors(['titolo' => 'Il titolo può avere al massimo 40 caratteri.']);

        $this->assertSame(0, DB::table('tc_codici')->count());
    }

    public function test_qr_generator_rejects_quantities_outside_one_to_two_hundred(): void
    {
        foreach ([0, 201] as $quantita) {
            Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
                ->set('quantita', $quantita)
                ->call('generaLiberi')
                ->assertHasErrors(['quantita' => 'Indica da 1 a 200 QR da generare.']);
        }

        $this->assertSame(0, DB::table('tc_codici')->count());
        $this->assertSame([], Storage::disk($this->disco)->files('protected/documenti/QrcodeText'));
    }

    public function test_qr_generator_limits_new_codes_to_two_hundred_per_day(): void
    {
        $mock = \Mockery::mock(QrSede::class)->makePartial();
        $mock->shouldReceive('genera')->times(200);
        $this->app->instance(QrSede::class, $mock);

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('quantita', 200)
            ->call('generaLiberi')
            ->assertHasNoErrors()
            ->assertSee('Creati da ITALPOL*000001 a ITALPOL*000200.');

        $this->assertSame(200, DB::table('tc_codici')->count());
        $this->assertSame('ITALPOL*000001', DB::table('tc_codici')->orderBy('codice')->value('codice'));
        $this->assertSame('ITALPOL*000200', DB::table('tc_codici')->orderByDesc('codice')->value('codice'));

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('quantita', 1)
            ->call('generaLiberi')
            ->assertHasErrors(['quantita' => 'Hai già generato 200 codici oggi.']);

        $this->assertSame(200, DB::table('tc_codici')->count());

        $this->travel(1)->days();
        $this->app->forgetInstance(QrSede::class);

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('quantita', 1)
            ->call('generaLiberi')
            ->assertHasNoErrors()
            ->assertSee('Creato ITALPOL*000201.');

        $this->assertSame(201, DB::table('tc_codici')->count());
    }

    public function test_qr_generator_rejects_a_batch_larger_than_the_codes_left_today(): void
    {
        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('quantita', 2)
            ->call('generaLiberi')
            ->assertHasNoErrors();

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('quantita', 199)
            ->call('generaLiberi')
            ->assertHasErrors(['quantita' => 'Oggi puoi generarne ancora 198.']);

        $this->assertSame(2, DB::table('tc_codici')->count());
    }

    public function test_qr_generator_removes_partial_codes_when_image_writing_fails(): void
    {
        $qr = new QrSede;
        $tentativi = 0;
        $mock = \Mockery::mock(QrSede::class)->makePartial();
        $mock->shouldReceive('genera')->twice()->andReturnUsing(function (string $codice, string $colore = '#000000', ?string $logo = null, string $titolo = '') use ($qr, &$tentativi): void {
            if (++$tentativi === 2) {
                throw new \RuntimeException('Errore di scrittura QR');
            }
            $qr->genera($codice);
        });
        $this->app->instance(QrSede::class, $mock);

        try {
            Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
                ->set('quantita', 2)
                ->call('generaLiberi');
            $this->fail("L'errore di scrittura avrebbe dovuto interrompere la generazione.");
        } catch (\RuntimeException $e) {
            $this->assertSame('Errore di scrittura QR', $e->getMessage());
        }

        $this->assertSame(0, DB::table('tc_codici')->count());
        $this->assertSame([], Storage::disk($this->disco)->files('protected/documenti/QrcodeText'));

        $this->app->forgetInstance(QrSede::class);
        $riprova = \Mockery::mock(QrSede::class)->makePartial();
        $riprova->shouldReceive('genera')->times(200);
        $this->app->instance(QrSede::class, $riprova);

        Livewire::actingAs($this->admin())->test(GeneratoreQr::class)
            ->set('quantita', 200)
            ->call('generaLiberi')
            ->assertHasNoErrors();

        $this->assertSame(200, DB::table('tc_codici')->count());
    }

    public function test_generating_a_qr_does_not_replace_an_existing_file(): void
    {
        $qr = app(QrSede::class);
        $percorso = $qr->percorso('ITALPOL*000123');
        Storage::disk($this->disco)->put($percorso, 'immagine esistente');

        try {
            $qr->genera('ITALPOL*000123');
            $this->fail('La generazione avrebbe dovuto segnalare il file esistente.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('esiste già', $e->getMessage());
        }

        $this->assertSame('immagine esistente', Storage::disk($this->disco)->get($percorso));
    }

    public function test_list_shows_only_sites_for_the_selected_customer(): void
    {
        $cliente = $this->cliente('Alpha');
        $altroCliente = $this->cliente('Beta');
        $this->sedeCompleta($cliente, 'Sede Alpha');
        $this->sedeCompleta($altroCliente, 'Sede Beta');

        Livewire::actingAs($this->admin())->test(Sedi::class)
            ->assertSee('Seleziona un cliente')
            ->assertDontSee('Sede Alpha')
            ->assertDontSee('Sede Beta')
            ->set('filtroCliente', $cliente)
            ->assertSee('Alpha')
            ->assertSee('Sede Alpha')
            ->assertDontSee('Sede Beta')
            ->assertDontSee('Tutti i clienti');
    }

    public function test_client_user_sees_only_its_sites_even_with_another_client_filter(): void
    {
        $cliente = $this->cliente('Alpha');
        $altroCliente = $this->cliente('Beta');
        $this->sedeCompleta($cliente, 'Sede Alpha');
        $this->sedeCompleta($altroCliente, 'Sede Beta');

        Livewire::actingAs($this->utente(3, 901, $cliente))->test(Sedi::class)
            ->assertSee('Sede Alpha')
            ->assertDontSee('Sede Beta')
            ->set('filtroCliente', $altroCliente)
            ->assertSee('Sede Alpha')
            ->assertDontSee('Sede Beta');
    }

    public function test_list_filters_sites_by_denominazione_email_and_telefono(): void
    {
        $cliente = $this->cliente();
        DB::table('tc_sedi')->insert([
            ['id_cliente' => $cliente, 'descrizione' => 'Deposito Centrale', 'email' => 'centrale@example.test', 'telefono' => '051222111'],
            ['id_cliente' => $cliente, 'descrizione' => 'Magazzino Nord', 'email' => 'nord@example.test', 'telefono' => '059333222'],
        ]);
        $lista = Livewire::actingAs($this->admin())->test(Sedi::class)->set('filtroCliente', $cliente);

        $lista->set('filtroDenominazione', 'Centrale')
            ->assertSee('Deposito Centrale')
            ->assertDontSee('Magazzino Nord')
            ->set('filtroDenominazione', '')
            ->set('filtroEmail', 'nord@example')
            ->assertSee('Magazzino Nord')
            ->assertDontSee('Deposito Centrale')
            ->set('filtroEmail', '')
            ->set('filtroTelefono', '051222')
            ->assertSee('Deposito Centrale')
            ->assertDontSee('Magazzino Nord');
    }

    public function test_edit_displays_customer_and_site_in_the_legacy_breadcrumb(): void
    {
        $cliente = $this->cliente('AREA 1');
        $sede = $this->sedeCompleta($cliente, 'GABBIOTTO SOPRA IL MARCATORE');

        Livewire::actingAs($this->admin())->test(SedeForm::class, ['sede' => $sede['sede']])
            ->assertSee('Sedi AREA 1')
            ->assertSee('Modifica / Gestisci sede e postazioni - AREA 1 - GABBIOTTO SOPRA IL MARCATORE', false)
            ->assertSee('value="'.$sede['codiceSede'].'" selected', false)
            ->assertSee('value="'.$sede['codicePostazione'].'" selected', false);
    }

    public function test_export_csv_respects_filters_and_roles(): void
    {
        $c = $this->cliente('Alpha');
        $b = $this->cliente('Beta');
        $this->sedeCompleta($c, 'Sede Alpha');
        $this->sedeCompleta($b, 'Sede Beta');

        $r = Livewire::actingAs($this->admin())->test(Sedi::class)->set('filtroCliente', $c)->call('esporta');
        $r->assertFileDownloaded('sedi-'.now()->format('Y-m-d').'.csv');
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Esportazione elenco Sedi')->count());

        Livewire::actingAs($this->utente(3, 901, $c))->test(Sedi::class)->call('esporta')->assertForbidden();
    }

    public function test_roles_and_visibility_on_delete(): void
    {
        $normale = $this->cliente('Reale');
        $test = $this->cliente('Prova', 1);
        $sn = $this->sedeCompleta($normale, 'Sede Reale');
        $st = $this->sedeCompleta($test, 'Sede Prova');

        foreach ([[3, 901, $normale], [4, 902, null]] as [$tipo, $id, $cl]) {
            Livewire::actingAs($this->utente($tipo, $id, $cl))->test(Sedi::class)->call('richiediEliminazione', $sn['sede'])->assertForbidden();
        }
        Livewire::actingAs($this->admin())->test(Sedi::class)->call('richiediEliminazione', $st['sede'])->assertNotFound();
        Livewire::actingAs($this->utente(1, 903))->test(Sedi::class)->call('richiediEliminazione', $sn['sede'])->assertNotFound();

        $this->assertSame(2, DB::table('tc_sedi')->count());
    }

    public function test_edit_cannot_move_sede_to_another_client(): void
    {
        $c = $this->cliente();
        $altro = $this->cliente('Beta');
        $s = $this->sedeCompleta($c);
        $nuovoCodice = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000123', 'stato' => 'C']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class, ['sede' => $s['sede']])
            ->set('f.cliente_id', $altro)
            ->set('f.descrizione', 'Rinominata')->set('f.indirizzo', 'Via Roma 1')->set('f.citta', 'Modena')->set('f.provincia', 'MO')
            ->set('f.lat', '44.5')->set('f.lng', '10.9')->set('f.distanza_max', 100)
            ->set('codiceSedeId', (string) $nuovoCodice)
            ->call('salva')->assertHasNoErrors();

        $riga = DB::table('tc_sedi')->find($s['sede']);
        $this->assertSame($c, (int) $riga->id_cliente);
        $this->assertSame('Rinominata', $riga->descrizione);
        $this->assertSame($nuovoCodice, (int) $riga->id_codice);
        $this->assertSame('A', DB::table('tc_codici')->find($nuovoCodice)->stato);
        $this->assertSame('S', DB::table('tc_codici')->find($nuovoCodice)->tipo);
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Aggiornamento dati sede Rinominata')->count());
    }

    public function test_create_assigns_selected_available_qr_codes_to_site_and_station(): void
    {
        $cliente = $this->cliente();
        $codiceSede = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000201', 'stato' => 'C']);
        $codicePostazione = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000202', 'stato' => 'C']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        $form = Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->assertSee('ITALPOL*000201')
            ->set('codiceSedeId', (string) $codiceSede)
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede QR')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9');

        $form->call('aggiungiPostazione');
        $chiavePostazione = array_key_first($form->get('postazioni'));
        $form->set("postazioni.$chiavePostazione.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiavePostazione.titolo", 'Ingresso')
            ->call('salva')->assertHasNoErrors();

        $sede = DB::table('tc_sedi')->first();
        $postazione = DB::table('tc_postazioni')->first();
        $this->assertSame($codiceSede, (int) $sede->id_codice);
        $this->assertSame($codicePostazione, (int) $postazione->id_codice);
        $this->assertSame('A', DB::table('tc_codici')->find($codiceSede)->stato);
        $this->assertSame('S', DB::table('tc_codici')->find($codiceSede)->tipo);
        $this->assertSame((int) $sede->id, DB::table('tc_codici')->find($codiceSede)->id_sede);
        $this->assertSame('A', DB::table('tc_codici')->find($codicePostazione)->stato);
        $this->assertSame('P', DB::table('tc_codici')->find($codicePostazione)->tipo);
        $this->assertSame((int) $postazione->id, DB::table('tc_codici')->find($codicePostazione)->id_postazione);
    }

    public function test_create_generates_qr_codes_when_none_are_available(): void
    {
        $cliente = $this->cliente();
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        $form = Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->assertSee('CODICE DA GENERARE')
            ->set('codiceSedeId', 'XX')
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede QR generati')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9');

        $form->call('aggiungiPostazione');
        $chiavePostazione = array_key_first($form->get('postazioni'));
        $form->set("postazioni.$chiavePostazione.codice_id", 'XX')
            ->set("postazioni.$chiavePostazione.titolo", 'Ingresso')
            ->call('salva')->assertHasNoErrors();

        $sede = DB::table('tc_sedi')->first();
        $postazione = DB::table('tc_postazioni')->first();
        $codiceSede = DB::table('tc_codici')->find($sede->id_codice);
        $codicePostazione = DB::table('tc_codici')->find($postazione->id_codice);
        $this->assertSame('ITALPOL*000001', $codiceSede->codice);
        $this->assertSame('A', $codiceSede->stato);
        $this->assertSame('S', $codiceSede->tipo);
        $this->assertSame((int) $sede->id, $codiceSede->id_sede);
        $this->assertSame('ITALPOL*000002', $codicePostazione->codice);
        $this->assertSame('A', $codicePostazione->stato);
        $this->assertSame('P', $codicePostazione->tipo);
        $this->assertSame((int) $postazione->id, $codicePostazione->id_postazione);
        foreach ([$codiceSede, $codicePostazione] as $codice) {
            $percorso = app(QrSede::class)->percorso($codice->codice);
            Storage::disk($this->disco)->assertExists($percorso);
            $this->assertSame('image/png', getimagesizefromstring(Storage::disk($this->disco)->get($percorso))['mime']);
        }
    }

    public function test_qr_storage_failure_does_not_save_a_partial_site(): void
    {
        $cliente = $this->cliente();
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);
        $qr = new QrSede;
        $tentativi = 0;
        $mock = \Mockery::mock(QrSede::class)->makePartial();
        $mock->shouldReceive('genera')->twice()->andReturnUsing(function (string $codice) use ($qr, &$tentativi): void {
            if (++$tentativi === 2) {
                throw new \RuntimeException('Errore di scrittura QR');
            }
            $qr->genera($codice);
        });
        $this->app->instance(QrSede::class, $mock);

        $form = Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', 'XX')
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede QR')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9')
            ->call('aggiungiPostazione');
        $chiave = array_key_first($form->get('postazioni'));

        try {
            $form->set("postazioni.$chiave.codice_id", 'XX')
                ->set("postazioni.$chiave.titolo", 'Ingresso')
                ->call('salva');
            $this->fail("L'errore di scrittura avrebbe dovuto interrompere il salvataggio.");
        } catch (\RuntimeException $e) {
            $this->assertSame('Errore di scrittura QR', $e->getMessage());
        }

        $this->assertSame(0, DB::table('tc_sedi')->count());
        $this->assertSame(0, DB::table('tc_postazioni')->count());
        $this->assertSame(0, DB::table('tc_codici')->count());
        $this->assertSame([], Storage::disk($this->disco)->files('protected/documenti/QrcodeText'));
    }

    public function test_edit_can_generate_new_qr_codes_when_none_are_available(): void
    {
        $cliente = $this->cliente();
        $sede = $this->sedeCompleta($cliente);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);
        $chiavePostazione = 'p'.$sede['postazione'];

        $form = Livewire::actingAs($this->admin())->test(SedeForm::class, ['sede' => $sede['sede']])
            ->assertSee('CODICE DA GENERARE')
            ->assertSee('ITALPOL*S'.$sede['sede'])
            ->assertSee('ITALPOL*P'.$sede['postazione'])
            ->set('codiceSedeId', 'XX')
            ->set("postazioni.$chiavePostazione.codice_id", 'XX')
            ->set('f.indirizzo', 'Via Roma 1')->set('f.citta', 'Modena')->set('f.provincia', 'MO')
            ->set('f.lat', '44.5')->set('f.lng', '10.9')->set('f.distanza_max', 100);

        $form->call('salva')->assertHasNoErrors();

        $sedeAggiornata = DB::table('tc_sedi')->find($sede['sede']);
        $postazioneAggiornata = DB::table('tc_postazioni')->find($sede['postazione']);
        $codiceSede = DB::table('tc_codici')->find($sedeAggiornata->id_codice);
        $codicePostazione = DB::table('tc_codici')->find($postazioneAggiornata->id_codice);
        $this->assertNotSame($sede['codiceSede'], (int) $sedeAggiornata->id_codice);
        $this->assertNotSame($sede['codicePostazione'], (int) $postazioneAggiornata->id_codice);
        $this->assertSame('A', $codiceSede->stato);
        $this->assertSame('S', $codiceSede->tipo);
        $this->assertSame((int) $sede['sede'], $codiceSede->id_sede);
        $this->assertSame('A', $codicePostazione->stato);
        $this->assertSame('P', $codicePostazione->tipo);
        $this->assertSame((int) $sede['sede'], $codicePostazione->id_sede);
        $this->assertSame((int) $sede['postazione'], $codicePostazione->id_postazione);
    }

    public function test_create_rejects_assigning_the_same_qr_to_site_and_station(): void
    {
        $cliente = $this->cliente();
        $codice = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000203', 'stato' => 'C']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        $form = Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', (string) $codice)
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede QR duplicato')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9');

        $form->call('aggiungiPostazione');
        $chiavePostazione = array_key_first($form->get('postazioni'));
        $form->set("postazioni.$chiavePostazione.codice_id", (string) $codice)
            ->set("postazioni.$chiavePostazione.titolo", 'Ingresso')
            ->call('salva')
            ->assertHasErrors(['codiceSedeId', "postazioni.$chiavePostazione.codice_id"]);

        $this->assertSame(0, DB::table('tc_sedi')->count());
        $this->assertSame('C', DB::table('tc_codici')->find($codice)->stato);
    }

    public function test_missing_station_title_shows_a_clear_message_and_opens_the_tab(): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->call('salva')
            ->assertOnlyHasErrors(["postazioni.$chiave.titolo" => 'Indica il titolo della postazione.'])
            ->assertDispatched('tab-errore', tab: 'postazioni')
            ->assertSee('Indica il titolo della postazione.')
            ->assertDontSee('Il campo postazioni.', false)
            ->assertSee('novalidate', false)
            ->assertSee('Qrcode associato *');

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_invalid_action_radius_opens_the_position_tab(): void
    {
        $cliente = $this->cliente();
        $codice = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000501', 'stato' => 'C']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', (string) $codice)
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede valida')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9')
            ->set('f.distanza_max', 1)
            ->call('salva')
            ->assertHasErrors(['f.distanza_max' => 'Il campo f.distanza max deve essere almeno 10.'])
            ->assertDispatched('tab-errore', tab: 'posizione')
            ->assertSee("Raggio d'azione");

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_station_title_over_255_characters_is_rejected(): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiave.titolo", str_repeat('a', 256))
            ->call('salva')
            ->assertOnlyHasErrors(["postazioni.$chiave.titolo" => 'Il titolo della postazione può avere al massimo 255 caratteri.']);

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_missing_station_qr_shows_a_clear_message(): void
    {
        [$form, $chiave] = $this->formConPostazione();

        $form->set("postazioni.$chiave.titolo", 'Ingresso')
            ->call('salva')
            ->assertOnlyHasErrors(["postazioni.$chiave.codice_id" => 'Associa un QR code alla postazione.'])
            ->assertSee('Associa un QR code alla postazione.')
            ->assertDispatched('tab-errore', tab: 'postazioni');

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    #[TestWith(['passaggi', '1.5', 'Il numero di passaggi deve essere un numero intero.'])]
    #[TestWith(['passaggi', '-1', 'Il numero di passaggi non può essere negativo.'])]
    #[TestWith(['passaggi', '1001', 'Il numero di passaggi non può superare 1000.'])]
    #[TestWith(['minuti', '2.5', 'I minuti di passaggio devono essere un numero intero.'])]
    #[TestWith(['minuti', '-3', 'I minuti di passaggio non possono essere negativi.'])]
    #[TestWith(['minuti', '1441', 'I minuti di passaggio non possono superare 1440.'])]
    public function test_invalid_station_numbers_show_a_clear_message(string $campo, string $valore, string $messaggio): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiave.titolo", 'Ingresso')
            ->set("postazioni.$chiave.$campo", $valore)
            ->call('salva')
            ->assertOnlyHasErrors(["postazioni.$chiave.$campo" => $messaggio])
            ->assertSee($messaggio)
            ->assertDontSee('Il campo postazioni.', false);

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_station_info_over_the_limit_is_rejected(): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiave.titolo", 'Ingresso')
            ->set("postazioni.$chiave.info", str_repeat('a', 65536))
            ->call('salva')
            ->assertOnlyHasErrors(["postazioni.$chiave.info" => 'Le informazioni della postazione sono troppo lunghe.']);

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_non_image_station_file_is_rejected(): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiave.titolo", 'Ingresso')
            ->set("immaginiPostazioni.$chiave", UploadedFile::fake()->create('nota.txt', 10, 'text/plain'))
            ->call('salva')
            ->assertOnlyHasErrors(["immaginiPostazioni.$chiave" => 'Carica un\'immagine per la postazione.'])
            ->assertSee('Carica un\'immagine per la postazione.');

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_station_image_over_4_mb_is_rejected(): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiave.titolo", 'Ingresso')
            ->set("immaginiPostazioni.$chiave", UploadedFile::fake()->image('foto.jpg')->size(4097))
            ->call('salva')
            ->assertOnlyHasErrors(["immaginiPostazioni.$chiave" => 'L\'immagine della postazione non può superare 4 MB.']);

        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_only_the_invalid_station_shows_the_title_error(): void
    {
        [$form, $prima, $codicePostazione] = $this->formConPostazione();
        $altroCodice = DB::table('tc_codici')->insertGetId(['codice' => 'ITALPOL*000403', 'stato' => 'C']);

        $form->call('aggiungiPostazione');
        $seconda = array_key_last($form->get('postazioni'));

        $form->set("postazioni.$prima.codice_id", (string) $codicePostazione)
            ->set("postazioni.$seconda.codice_id", (string) $altroCodice)
            ->set("postazioni.$seconda.titolo", 'Uscita')
            ->call('salva')
            ->assertOnlyHasErrors(["postazioni.$prima.titolo" => 'Indica il titolo della postazione.']);

        $this->assertSame(1, substr_count($form->html(), 'Indica il titolo della postazione.'));
        $this->assertSame(0, DB::table('tc_sedi')->count());
    }

    public function test_zero_station_numbers_are_saved(): void
    {
        [$form, $chiave, $codicePostazione] = $this->formConPostazione();

        $form->set("postazioni.$chiave.codice_id", (string) $codicePostazione)
            ->set("postazioni.$chiave.titolo", 'Ingresso')
            ->set("postazioni.$chiave.passaggi", '0')
            ->set("postazioni.$chiave.minuti", '0')
            ->call('salva')
            ->assertHasNoErrors();

        $postazione = DB::table('tc_postazioni')->first();
        $this->assertSame(0, (int) $postazione->passaggi);
        $this->assertSame(0, (int) $postazione->minuti);
    }

    public function test_create_rejects_qr_code_assigned_to_another_site(): void
    {
        $cliente = $this->cliente();
        $esistente = $this->sedeCompleta($cliente);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', (string) $esistente['codiceSede'])
            ->set('f.cliente_id', $cliente)->set('f.descrizione', 'Sede Non Valida')->set('f.indirizzo', 'Via Roma 1')
            ->set('f.citta', 'Modena')->set('f.provincia', 'MO')->set('f.lat', '44.5')->set('f.lng', '10.9')
            ->call('salva')->assertHasErrors(['codiceSedeId']);

        $this->assertSame(1, DB::table('tc_sedi')->count());
        $this->assertSame('A', DB::table('tc_codici')->find($esistente['codiceSede'])->stato);
    }

    public function test_save_without_coordinates_warns_but_keeps_old_behaviour(): void
    {
        $c = $this->cliente();
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', 'XX')->set('f.cliente_id', $c)->set('f.descrizione', 'Nuova')->set('f.indirizzo', 'Via Roma 1')->set('f.citta', 'Modena')->set('f.provincia', 'MO')
            ->call('salva')->assertHasNoErrors();

        $this->assertSame(1, DB::table('tc_sedi')->count());
        $this->assertSame(0.0, (float) DB::table('tc_sedi')->value('lat'));
        $this->assertSame(1, DB::table('tc_codici')->where('tipo', 'S')->where('stato', 'A')->count());
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Creazione sede Nuova')->count());
        $this->assertStringContainsString('senza coordinate', (string) session('avviso'));
    }

    public function test_save_without_coordinates_geocodes_the_address_when_the_service_is_configured(): void
    {
        config(['services.google_maps.key' => 'chiave']);
        Http::fake(['maps.googleapis.com/*' => Http::response(['results' => [['geometry' => ['location' => ['lat' => 44.65, 'lng' => 10.93]]]]])]);
        $c = $this->cliente();
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('codiceSedeId', 'XX')->set('f.cliente_id', $c)->set('f.descrizione', 'Nuova')->set('f.indirizzo', 'Via Roma 1')->set('f.cap', '41100')->set('f.citta', 'Modena')->set('f.provincia', 'MO')
            ->call('salva')->assertHasNoErrors();

        $this->assertSame(44.65, (float) DB::table('tc_sedi')->value('lat'));
        $this->assertSame(10.93, (float) DB::table('tc_sedi')->value('lng'));
        Http::assertSent(fn ($r) => $r['address'] === 'Via Roma 1 41100 Modena');
        $this->assertNull(session('avviso'));
    }

    private function creaSchema(): void
    {
        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            foreach (['nome', 'cognome', 'email', 'user', 'password'] as $c) {
                $table->string($c)->nullable();
            }
            $table->unsignedInteger('id_cliente')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
            $table->integer('isTest')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_codice')->nullable();
            foreach (['descrizione', 'indirizzo', 'cap', 'telefono', 'email', 'note', 'colore', 'pos', 'foto'] as $c) {
                $table->string($c)->nullable();
            }
            $table->integer('citta_id')->nullable();
            $table->integer('provincia_id')->nullable();
            $table->float('lat')->default(0);
            $table->float('lng')->default(0);
            $table->date('inizio_contratto')->nullable();
            $table->date('fine_contratto')->nullable();
            $table->float('tariffa')->nullable();
            $table->integer('distanza')->default(80);
            foreach (['lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'] as $c) {
                $table->integer($c)->nullable();
            }
        });
        Schema::create('tc_postazioni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_codice')->nullable();
            $table->string('titolo')->nullable();
            $table->text('info')->nullable();
            $table->string('file')->nullable();
            $table->integer('passaggi')->nullable();
            $table->integer('minuti')->nullable();
        });
        Schema::create('tc_codici', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('tipo')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_postazione')->nullable();
            $table->string('codice')->nullable();
            $table->string('stato')->nullable();
        });
        Schema::create('tc_turni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->string('stato')->nullable();
        });
        Schema::create('tc_turni_step', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_sede')->nullable();
        });
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_sede')->nullable();
        });
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_tipo')->nullable();
            $table->string('tipo')->nullable();
            $table->string('file')->nullable();
        });
        Schema::create('_provincia', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('sigla')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('comune')->nullable();
            $table->string('sigla_provincia')->nullable();
            $table->unsignedInteger('id_provincia')->nullable();
        });
        Schema::create('_tracking', function (Blueprint $table): void {
            $table->integer('id')->primary();
            foreach (['addr', 'host', 'agent', 'pagina', 'data_view', 'action'] as $c) {
                $table->string($c)->nullable();
            }
            $table->unsignedInteger('id_user')->nullable();
        });
    }
}

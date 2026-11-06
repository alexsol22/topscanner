<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Clienti;
use App\Livewire\Operatori;
use App\Mail\NotificaHtml;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientiOperatoriSuiteTest extends TestCase
{
    private string $disco;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        $this->disco = config('filesystems.files_disk');
        Storage::fake($this->disco);
        Mail::fake();
        $this->creaSchema();
        DB::table('tc_admin')->insert(['id' => 100, 'user_type' => 4, 'attivo' => 'N', 'nome' => 'Segnaposto', 'cognome' => 'Id', 'email' => 'seg@t.it']);
    }

    private function utente(int $tipo, int $id = 900, ?int $idCliente = null): User
    {
        DB::table('tc_admin')->insertOrIgnore(['id' => $id, 'user_type' => $tipo, 'attivo' => 'Y', 'nome' => 'Test', 'cognome' => "T$id", 'id_cliente' => $idCliente]);

        return User::find($id);
    }

    private function super(): User
    {
        return $this->utente(1, 901);
    }

    private function admin(): User
    {
        return $this->utente(2, 900);
    }

    private function cliente(string $nome = 'Alpha Srl', ?int $isTest = null): int
    {
        return DB::table('tc_clienti')->insertGetId(['ragione_sociale' => $nome, 'isTest' => $isTest, 'email' => 'c@t.it', 'piva' => '12345678901']);
    }

    private function operatore(string $nome = 'Mario', string $cognome = 'Rossi', string $email = 'm@t.it'): int
    {
        return DB::table('tc_admin')->insertGetId(['user_type' => 4, 'attivo' => 'Y', 'nome' => $nome, 'cognome' => $cognome, 'email' => $email, 'codice' => 'OP1']);
    }

    private function datiCliente(array $extra = []): array
    {
        return $extra + ['ragione_sociale' => 'Beta Spa', 'piva' => '98765432109', 'email' => 'beta@t.it', 'accesso' => false];
    }

    // ---------- CLIENTI ----------

    public function test_clienti_list_search_and_pagination(): void
    {
        foreach (range(1, 12) as $n) {
            $this->cliente("Cliente $n");
        }
        $this->cliente('Zeta Unico');

        Livewire::actingAs($this->admin())->test(Clienti::class)
            ->assertSee('Cliente 1')->assertDontSee('Zeta Unico')
            ->set('cerca', 'Zeta')->assertSee('Zeta Unico')->assertDontSee('Cliente 1');
    }

    public function test_clienti_create_validates_every_field(): void
    {
        $casi = [
            ['f.ragione_sociale', ''], ['f.piva', '123'], ['f.piva', 'abcdefghijk'], ['f.email', 'non-email'],
            ['f.pec', 'x'], ['f.telefono', 'abc'], ['f.cellulare', str_repeat('1', 21)], ['f.colore', 'rosso'], ['f.cap', '123456'],
        ];
        foreach ($casi as [$campo, $valore]) {
            Livewire::actingAs($this->admin())->test(Clienti::class)->call('nuovo')
                ->set('f', $this->datiCliente())->set($campo, $valore)->call('salva')->assertHasErrors([$campo]);
        }
        $this->assertSame(0, DB::table('tc_clienti')->count());
    }

    public function test_clienti_create_and_edit(): void
    {
        $c = Livewire::actingAs($this->admin())->test(Clienti::class)->call('nuovo')
            ->set('f', $this->datiCliente())->call('salva')->assertHasNoErrors()->assertSet('modal', false);
        $id = DB::table('tc_clienti')->value('id');
        $this->assertSame('Beta Spa', DB::table('tc_clienti')->where('id', $id)->value('ragione_sociale'));
        $this->assertNull(DB::table('tc_clienti')->where('id', $id)->value('isTest'));

        $c->call('modifica', $id)->assertSet('f.ragione_sociale', 'Beta Spa')->set('f.ragione_sociale', 'Beta Nuova')->call('salva')->assertHasNoErrors();
        $this->assertSame('Beta Nuova', DB::table('tc_clienti')->where('id', $id)->value('ragione_sociale'));
        $this->assertSame(1, DB::table('tc_clienti')->count());
    }

    public function test_super_admin_sees_and_creates_only_test_clients(): void
    {
        $normale = $this->cliente('Reale');
        $test = $this->cliente('Di Prova', 1);

        Livewire::actingAs($this->super())->test(Clienti::class)
            ->assertSee('Di Prova')->assertDontSee('Reale')
            ->call('nuovo')->set('f', $this->datiCliente())->call('salva');
        $this->assertSame(1, (int) DB::table('tc_clienti')->where('ragione_sociale', 'Beta Spa')->value('isTest'));

        Livewire::actingAs($this->admin())->test(Clienti::class)->assertSee('Reale')->assertDontSee('Di Prova');
        $this->assertNotNull($normale.$test);
    }

    public function test_admin_cannot_edit_or_delete_test_clients_and_vice_versa(): void
    {
        $test = $this->cliente('Di Prova', 1);
        Livewire::actingAs($this->admin())->test(Clienti::class)->call('modifica', $test)->assertNotFound();
        Livewire::actingAs($this->admin())->test(Clienti::class)->call('richiediEliminazione', $test)->assertNotFound();
        $this->assertSame(1, DB::table('tc_clienti')->count());
    }

    public function test_client_role_and_operator_cannot_delete_or_see_clients(): void
    {
        $id = $this->cliente();
        $cli = $this->utente(3, 910, $id);
        $op = $this->utente(4, 911);

        Livewire::actingAs($cli)->test(Clienti::class)->assertForbidden();
        Livewire::actingAs($op)->test(Clienti::class)->assertForbidden();
        Livewire::actingAs($cli)->test(Operatori::class)->assertForbidden();
        Livewire::actingAs($op)->test(Operatori::class)->assertForbidden();
        $this->assertSame(1, DB::table('tc_clienti')->count());
    }

    public function test_access_flag_creates_single_account_with_email(): void
    {
        $c = Livewire::actingAs($this->admin())->test(Clienti::class)->call('nuovo')
            ->set('f', $this->datiCliente(['accesso' => true]))->call('salva')->assertHasNoErrors();
        $id = DB::table('tc_clienti')->value('id');
        $this->assertSame(1, DB::table('tc_admin')->where('id_cliente', $id)->where('user_type', 3)->count());
        Mail::assertSentCount(1);

        $c->call('modifica', $id)->set('f.accesso', true)->call('salva');
        $this->assertSame(1, DB::table('tc_admin')->where('id_cliente', $id)->count());
        Mail::assertSentCount(1);
        Mail::assertSent(NotificaHtml::class);
    }

    public function test_preferred_and_excluded_operators_are_replaced_on_save(): void
    {
        $op1 = $this->operatore();
        $op2 = $this->operatore('Luca', 'Bianchi', 'l@t.it');
        $c = Livewire::actingAs($this->admin())->test(Clienti::class)->call('nuovo')
            ->set('f', $this->datiCliente())->set('preferiti', [$op1, $op2])->set('escludi', [$op1])->call('salva')->assertHasNoErrors();
        $id = DB::table('tc_clienti')->value('id');
        $this->assertSame(2, DB::table('tc_operatori_preferiti')->where('id_cliente', $id)->count());
        $this->assertSame(1, DB::table('tc_operatori_escludi')->where('id_cliente', $id)->count());

        $c->call('modifica', $id)->set('preferiti', [$op2])->set('escludi', [])->call('salva');
        $this->assertSame([$op2], DB::table('tc_operatori_preferiti')->where('id_cliente', $id)->pluck('id_operatore')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(0, DB::table('tc_operatori_escludi')->count());
    }

    public function test_client_documents_upload_validation_and_delete(): void
    {
        $id = $this->cliente();
        $c = Livewire::actingAs($this->admin())->test(Clienti::class)->call('modifica', $id);

        $c->call('aggiungiDocumento')->assertHasErrors(['titoloDocumento', 'fileDocumento']);
        $c->set('titoloDocumento', 'Contratto')->set('fileDocumento', UploadedFile::fake()->create('virus.exe', 10))->call('aggiungiDocumento')->assertHasErrors(['fileDocumento']);
        $c->set('titoloDocumento', 'Contratto')->set('fileDocumento', UploadedFile::fake()->create('grande.pdf', 20000, 'application/pdf'))->call('aggiungiDocumento')->assertHasErrors(['fileDocumento']);
        $this->assertSame(0, DB::table('tc_documenti')->count());

        $c->set('titoloDocumento', 'Contratto')->set('fileDocumento', UploadedFile::fake()->create('ok.pdf', 10, 'application/pdf'))->call('aggiungiDocumento')->assertHasNoErrors();
        $doc = DB::table('tc_documenti')->first();
        $this->assertSame('C', $doc->tipo);
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$id/{$doc->file}");

        $c->call('eliminaDocumento', $doc->id);
        $this->assertSame(0, DB::table('tc_documenti')->count());
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$id/{$doc->file}");
    }

    public function test_client_cannot_delete_document_of_other_client(): void
    {
        $a = $this->cliente('A');
        $b = $this->cliente('B');
        DB::table('tc_documenti')->insert(['id_tipo' => $b, 'tipo' => 'C', 'file' => 'x.pdf']);

        Livewire::actingAs($this->admin())->test(Clienti::class)->call('modifica', $a)->call('eliminaDocumento', 1)->assertNotFound();
        $this->assertSame(1, DB::table('tc_documenti')->count());
    }

    public function test_client_delete_cascade_is_complete_and_isolated(): void
    {
        $a = $this->cliente('A');
        $b = $this->cliente('B');
        $op = $this->operatore();
        foreach ([$a, $b] as $c) {
            DB::table('tc_sedi')->insert(['id_cliente' => $c]);
            DB::table('tc_turni')->insert(['id_cliente' => $c]);
            DB::table('tc_postazioni')->insert(['id_cliente' => $c]);
            DB::table('tc_turni_step')->insert(['id_cliente' => $c]);
            DB::table('tc_operatori_preferiti')->insert(['id_cliente' => $c, 'id_operatore' => $op]);
            DB::table('tc_operatori_escludi')->insert(['id_cliente' => $c, 'id_operatore' => $op]);
            DB::table('tc_admin')->insert(['id_cliente' => $c, 'user_type' => 3, 'attivo' => 'Y']);
            DB::table('tc_documenti')->insert(['id_tipo' => $c, 'tipo' => 'C', 'file' => 'f.pdf']);
            Storage::disk($this->disco)->put("protected/documenti/Cliente_$c/Sede_1/foto.jpg", 'x');
        }
        DB::table('tc_documenti')->insert(['id_tipo' => $a, 'tipo' => 'O', 'file' => 'op.pdf']);

        Livewire::actingAs($this->admin())->test(Clienti::class)
            ->call('richiediEliminazione', $a)->assertSet('eliminaId', $a)->assertSee('conferma')
            ->set('conferma', 'sbagliato')->call('elimina')->assertHasErrors(['conferma']);
        $this->assertSame(2, DB::table('tc_clienti')->count());

        Livewire::actingAs($this->admin())->test(Clienti::class)
            ->call('richiediEliminazione', $a)->set('conferma', '  CONFERMA ')->call('elimina')->assertHasNoErrors()->assertSet('eliminaId', null);

        $this->assertSame(0, DB::table('tc_clienti')->where('id', $a)->count());
        foreach (['tc_sedi', 'tc_turni', 'tc_postazioni', 'tc_turni_step', 'tc_operatori_preferiti', 'tc_operatori_escludi'] as $t) {
            $this->assertSame(0, DB::table($t)->where('id_cliente', $a)->count(), $t);
            $this->assertSame(1, DB::table($t)->where('id_cliente', $b)->count(), "$t di B intatto");
        }
        $this->assertSame(0, DB::table('tc_admin')->where('id_cliente', $a)->count());
        $this->assertSame(1, DB::table('tc_admin')->where('id_cliente', $b)->count());
        $this->assertSame(1, DB::table('tc_documenti')->where('tipo', 'C')->count());
        $this->assertSame(1, DB::table('tc_documenti')->where('tipo', 'O')->count(), 'documenti operatore con stesso id non toccati');
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$a/Sede_1/foto.jpg");
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$b/Sede_1/foto.jpg");
        $this->assertNotNull(DB::table('tc_admin')->find($op), 'operatore non eliminato');
    }

    public function test_client_delete_cancel_and_tampering(): void
    {
        $a = $this->cliente('A');
        $b = $this->cliente('B');
        Livewire::actingAs($this->admin())->test(Clienti::class)->call('richiediEliminazione', $a)->call('annullaEliminazione')->assertSet('eliminaId', null);
        Livewire::actingAs($this->admin())->test(Clienti::class)->set('conferma', 'conferma')->call('elimina')->assertNotFound();
        $this->assertSame(2, DB::table('tc_clienti')->count());

        // manomissione: puntare a un cliente non visibile (di test) viene rifiutato
        $test = $this->cliente('Di Prova', 1);
        Livewire::actingAs($this->admin())->test(Clienti::class)->call('richiediEliminazione', $a)->set('eliminaId', $test)->set('conferma', 'conferma')->call('elimina')->assertNotFound();
        $this->assertSame(3, DB::table('tc_clienti')->count());
    }

    // ---------- OPERATORI / UTENTI ----------

    private function datiOperatore(array $extra = []): array
    {
        return $extra + ['name' => 'Anna', 'cognome' => 'Verdi', 'email' => 'anna@t.it', 'cellulare' => '333', 'ruolo' => 'operatore', 'cliente_id' => '', 'codice' => 'OP9',
            'costo_orario' => '10', 'password' => '', 'attivo' => true, 'colore' => '#1d3e67', 'data_nascita' => '', 'luogo_nascita' => '', 'indirizzo' => '', 'provincia' => '', 'iban' => ''];
    }

    public function test_operators_list_search_and_hidden_users(): void
    {
        $this->operatore('Mario', 'Rossi', 'm@t.it');
        $this->operatore('Luca', 'Bianchi', 'l@t.it');
        DB::table('tc_admin')->insert(['id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Nascosto', 'cognome' => 'Root']);

        Livewire::actingAs($this->admin())->test(Operatori::class)
            ->assertSee('Mario')->assertSee('Luca')->assertDontSee('Nascosto')
            ->set('cerca', 'Bianchi')->assertSee('Luca')->assertDontSee('Mario');
    }

    public function test_operator_create_validation_and_unique_email(): void
    {
        $this->operatore('Mario', 'Rossi', 'dup@t.it');
        $casi = [['f.name', ''], ['f.cognome', ''], ['f.email', 'x'], ['f.email', 'dup@t.it'], ['f.cellulare', ''], ['f.cellulare', '33a'], ['f.codice', ''], ['f.colore', 'rosso'], ['f.costo_orario', '-1'], ['f.password', 'corta'], ['f.ruolo', 'boh']];
        foreach ($casi as [$campo, $valore]) {
            Livewire::actingAs($this->admin())->test(Operatori::class)->call('nuovo')
                ->set('f', $this->datiOperatore())->set($campo, $valore)->call('salva')->assertHasErrors([$campo]);
        }
        $this->assertSame(2, DB::table('tc_admin')->where('user_type', 4)->count());
    }

    public function test_operator_creation_emails_credentials_and_is_tracked(): void
    {
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('nuovo')
            ->set('f', $this->datiOperatore())->call('salva')->assertHasNoErrors();

        Mail::assertSentCount(1);
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Nuovo operatore anna@t.it')->count());
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Visualizzazione elenco operatori ')->count());
    }

    public function test_operator_create_edit_and_email_unique_ignores_self(): void
    {
        $c = Livewire::actingAs($this->admin())->test(Operatori::class)->call('nuovo')
            ->set('f', $this->datiOperatore())->call('salva')->assertHasNoErrors();
        $id = DB::table('tc_admin')->where('email', 'anna@t.it')->value('id');
        $this->assertNotNull($id);
        $this->assertStringStartsWith('$2y$', DB::table('tc_admin')->find($id)->password);

        $c->call('modifica', $id)->set('f.cognome', 'Neri')->call('salva')->assertHasNoErrors();
        $this->assertSame('Neri', DB::table('tc_admin')->find($id)->cognome);
    }

    public function test_client_role_requires_client_and_forces_link(): void
    {
        $cliente = $this->cliente();
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('nuovo')
            ->set('f', $this->datiOperatore(['ruolo' => 'cliente']))->call('salva')->assertHasErrors(['f.cliente_id']);
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('nuovo')
            ->set('f', $this->datiOperatore(['ruolo' => 'cliente', 'cliente_id' => $cliente]))->call('salva')->assertHasNoErrors();
        $this->assertSame($cliente, (int) DB::table('tc_admin')->where('email', 'anna@t.it')->value('id_cliente'));
    }

    public function test_only_super_admin_can_manage_admins(): void
    {
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('nuovo')
            ->set('f', $this->datiOperatore(['ruolo' => 'admin']))->call('salva')->assertForbidden();
        $this->assertSame(0, DB::table('tc_admin')->where('email', 'anna@t.it')->count());

        Livewire::actingAs($this->super())->test(Operatori::class)->call('nuovo')
            ->set('f', $this->datiOperatore(['ruolo' => 'admin']))->call('salva')->assertHasNoErrors();
        $this->assertSame(2, (int) DB::table('tc_admin')->where('email', 'anna@t.it')->value('user_type'));

        $idAdmin = DB::table('tc_admin')->where('email', 'anna@t.it')->value('id');
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('modifica', $idAdmin)->assertNotFound();
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('richiediEliminazione', $idAdmin)->assertNotFound();
        Livewire::actingAs($this->admin())->test(Operatori::class)->call('attivaDisattiva', $idAdmin)->assertNotFound();
    }

    public function test_toggle_active_and_no_self_actions(): void
    {
        $id = $this->operatore();
        $admin = $this->admin();
        Livewire::actingAs($admin)->test(Operatori::class)->call('attivaDisattiva', $id);
        $this->assertSame('N', DB::table('tc_admin')->find($id)->attivo);
        Livewire::actingAs($admin)->test(Operatori::class)->call('attivaDisattiva', $id);
        $this->assertSame('Y', DB::table('tc_admin')->find($id)->attivo);

        $super = $this->super();
        Livewire::actingAs($super)->test(Operatori::class)->call('attivaDisattiva', $super->id)->assertForbidden();
        Livewire::actingAs($super)->test(Operatori::class)->call('richiediEliminazione', $super->id)->assertForbidden();
        Livewire::actingAs($admin)->test(Operatori::class)->call('richiediEliminazione', $admin->id)->assertNotFound();
        $this->assertNotNull(DB::table('tc_admin')->find($super->id));
    }

    public function test_operator_documents_formazioni_and_attestati_lifecycle(): void
    {
        $id = $this->operatore();
        DB::table('_certificati')->insert(['id' => 1, 'nome' => 'Primo soccorso']);
        $c = Livewire::actingAs($this->admin())->test(Operatori::class)->call('modifica', $id);

        $c->call('aggiungiFormazione')->assertHasErrors(['formazione.descrizione', 'fileFormazione']);
        $c->set('formazione', ['descrizione' => 'Corso', 'inizio' => '2026-05-10', 'fine' => '2026-05-01'])
            ->set('fileFormazione', UploadedFile::fake()->create('c.pdf', 10, 'application/pdf'))->call('aggiungiFormazione')->assertHasErrors(['formazione.fine']);
        $c->set('formazione', ['descrizione' => 'Corso', 'inizio' => '2026-05-01', 'fine' => '2026-05-10'])->call('aggiungiFormazione')->assertHasNoErrors();

        $c->call('aggiungiAttestato')->assertHasErrors(['attestato.tipologia', 'fileAttestato']);
        $c->set('attestato', ['tipologia' => 99])->set('fileAttestato', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->call('aggiungiAttestato')->assertHasErrors(['attestato.tipologia']);
        $c->set('attestato', ['tipologia' => 1, 'descrizione' => 'BLS', 'scadenza' => '2027-01-01'])->call('aggiungiAttestato')->assertHasNoErrors();
        $c->set('attestato', ['tipologia' => 1])->set('fileAttestato', UploadedFile::fake()->create('x.exe', 10))->call('aggiungiAttestato')->assertHasErrors(['fileAttestato']);

        $this->assertSame(2, DB::table('tc_documenti')->where('tipo', 'O')->where('id_tipo', $id)->count());
        $att = DB::table('tc_documenti')->where('formazione', 'N')->first();
        $c->call('aggiornaScadenza', $att->id, '2028-02-02');
        $this->assertSame('2028-02-02', substr((string) DB::table('tc_documenti')->find($att->id)->scadenza, 0, 10));

        foreach (DB::table('tc_documenti')->get() as $d) {
            Storage::disk($this->disco)->assertExists("protected/documenti/Operatore_$id/{$d->file}");
            $c->call('eliminaDocumento', $d->id);
            Storage::disk($this->disco)->assertMissing("protected/documenti/Operatore_$id/{$d->file}");
        }
        $this->assertSame(0, DB::table('tc_documenti')->count());
    }

    public function test_cannot_touch_documents_or_materials_of_other_operator(): void
    {
        $a = $this->operatore();
        $b = $this->operatore('Luca', 'Bianchi', 'l@t.it');
        DB::table('tc_documenti')->insert(['id_tipo' => $b, 'tipo' => 'O', 'formazione' => 'N', 'file' => 'x.pdf']);
        DB::table('tc_materiali')->insert(['id' => 1, 'id_operatore' => $b, 'id_materiale' => 1, 'consegnato' => 'N']);

        $c = Livewire::actingAs($this->admin())->test(Operatori::class)->call('modifica', $a);
        $base = fn () => Livewire::actingAs($this->admin())->test(Operatori::class)->call('modifica', $a);
        $base()->call('eliminaDocumento', 1)->assertNotFound();
        $base()->call('eliminaMateriale', 1)->assertNotFound();
        $base()->call('salvaMateriale', 1);
        $this->assertNull(DB::table('tc_materiali')->find(1)->taglia);
        $this->assertSame(1, DB::table('tc_documenti')->count());
        $this->assertSame(1, DB::table('tc_materiali')->count());
    }

    public function test_operator_delete_cascade_complete_and_isolated(): void
    {
        $a = $this->operatore();
        $b = $this->operatore('Luca', 'Bianchi', 'l@t.it');
        foreach ([$a, $b] as $o) {
            DB::table('tc_materiali')->insert(['id' => $o, 'id_operatore' => $o, 'id_materiale' => 1, 'consegnato' => 'N']);
            DB::table('tc_documenti')->insert(['id_tipo' => $o, 'tipo' => 'O', 'file' => "f$o.pdf"]);
            foreach (['tc_operatori_preferiti', 'tc_operatori_escludi', 'tc_documenti_view', 'tc_bacheca_view'] as $t) {
                DB::table($t)->insert(['id_operatore' => $o]);
            }
            Storage::disk($this->disco)->put("protected/documenti/Operatore_$o/f$o.pdf", 'x');
        }
        DB::table('tc_documenti')->insert(['id_tipo' => $a, 'tipo' => 'C', 'file' => 'cli.pdf']);

        Livewire::actingAs($this->admin())->test(Operatori::class)
            ->call('richiediEliminazione', $a)->assertSet('eliminaId', $a)
            ->set('conferma', 'no')->call('elimina')->assertHasErrors(['conferma']);
        $this->assertNotNull(DB::table('tc_admin')->find($a));

        Livewire::actingAs($this->admin())->test(Operatori::class)
            ->call('richiediEliminazione', $a)->set('conferma', 'Conferma')->call('elimina')->assertHasNoErrors();

        $this->assertNull(DB::table('tc_admin')->find($a));
        $this->assertNotNull(DB::table('tc_admin')->find($b));
        foreach (['tc_materiali', 'tc_operatori_preferiti', 'tc_operatori_escludi', 'tc_documenti_view', 'tc_bacheca_view'] as $t) {
            $this->assertSame(0, DB::table($t)->where('id_operatore', $a)->count(), $t);
            $this->assertSame(1, DB::table($t)->where('id_operatore', $b)->count(), "$t di B intatto");
        }
        $this->assertSame(0, DB::table('tc_documenti')->where('tipo', 'O')->where('id_tipo', $a)->count());
        $this->assertSame(1, DB::table('tc_documenti')->where('tipo', 'C')->where('id_tipo', $a)->count(), 'documento cliente stesso id intatto');
        Storage::disk($this->disco)->assertMissing("protected/documenti/Operatore_$a/f$a.pdf");
        Storage::disk($this->disco)->assertExists("protected/documenti/Operatore_$b/f$b.pdf");
    }

    public function test_operator_with_shift_history_cannot_be_deleted(): void
    {
        $a = $this->operatore();
        DB::table('tc_gda')->insert(['id_operatore' => $a]);

        Livewire::actingAs($this->admin())->test(Operatori::class)
            ->call('richiediEliminazione', $a)->assertSee('storico')->set('conferma', 'conferma')->call('elimina')->assertHasErrors(['conferma']);
        $this->assertNotNull(DB::table('tc_admin')->find($a));
    }

    public function test_client_role_cannot_access_operator_management_actions(): void
    {
        $a = $this->operatore();
        $componente = Livewire::actingAs($this->admin())->test(Operatori::class)->call('richiediEliminazione', $a)->set('conferma', 'conferma');
        $cli = $this->utente(3, 920, $this->cliente());

        // stesso componente già montato, ma richiesta fatta da un utente cliente
        $this->actingAs($cli);
        $componente->call('elimina')->assertForbidden();
        $this->assertNotNull(DB::table('tc_admin')->find($a), 'un cliente non deve poter eliminare operatori');
    }

    private function creaSchema(): void
    {
        Schema::create('_tracking', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('id_user')->nullable();
            $table->string('addr')->nullable();
            $table->string('host')->nullable();
            $table->string('agent')->nullable();
            $table->string('pagina')->nullable();
            $table->dateTime('data_view')->nullable();
            $table->text('action')->nullable();
        });
        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            foreach (['nome', 'cognome', 'email', 'user', 'cellulare', 'codice', 'colore', 'data_nascita', 'luogo_nascita', 'indirizzo', 'iban', 'password', 'avatar'] as $colonna) {
                $table->string($colonna)->nullable();
            }
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('provincia')->nullable();
            $table->float('costo_orario')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['accesso', 'ragione_sociale', 'codice_fiscale', 'piva', 'indirizzo', 'cap', 'nome', 'cognome', 'telefono', 'cellulare', 'fax', 'email', 'pec', 'colore', 'pos'] as $colonna) {
                $table->string($colonna)->nullable();
            }
            $table->integer('citta_id')->nullable();
            $table->integer('provincia_id')->nullable();
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->integer('isTest')->nullable();
        });
        foreach (['tc_sedi', 'tc_turni', 'tc_postazioni', 'tc_turni_step'] as $t) {
            Schema::create($t, function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('id_cliente')->nullable();
                $table->unsignedInteger('id_operatore')->nullable();
            });
        }
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_operatore')->nullable();
        });
        foreach (['tc_operatori_preferiti', 'tc_operatori_escludi'] as $t) {
            Schema::create($t, function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('id_cliente')->nullable();
                $table->unsignedInteger('id_operatore')->nullable();
            });
        }
        foreach (['tc_documenti_view', 'tc_bacheca_view'] as $t) {
            Schema::create($t, function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('id_operatore')->nullable();
            });
        }
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_tipo')->nullable();
            $table->string('tipo')->nullable();
            foreach (['formazione', 'descrizione', 'file', 'icona', 'admin'] as $colonna) {
                $table->string($colonna)->nullable();
            }
            $table->date('inizio')->nullable();
            $table->date('fine')->nullable();
            $table->date('scadenza')->nullable();
            $table->unsignedInteger('tipologia')->nullable();
        });
        Schema::create('tc_materiali', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->unsignedInteger('id_materiale')->nullable();
            $table->string('descrizione')->nullable();
            $table->string('taglia')->nullable();
            $table->date('riconsegna')->nullable();
            $table->string('consegnato')->nullable();
        });
        Schema::create('_materiali', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
        });
        Schema::create('_certificati', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
        });
        Schema::create('_provincia', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
            $table->string('sigla')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_provincia')->nullable();
            $table->string('comune')->nullable();
        });
        Schema::create('tc_messaggi', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('giorni')->nullable();
        });
        DB::table('tc_messaggi')->insert(['id' => 5, 'giorni' => 15]);
        DB::table('_materiali')->insert(['id' => 1, 'nome' => 'Giacca']);
    }
}

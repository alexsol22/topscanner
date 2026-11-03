<?php

namespace Tests\Unit;

use App\Services\Comunicazioni;
use PHPUnit\Framework\TestCase;

class ComunicazioniTest extends TestCase
{
    public function test_testo_sostituisce_i_segnaposto_del_modello(): void
    {
        $testo = (new Comunicazioni)->testo(
            'Ciao [OPERATORE], turno il [DATA TURNO] alle [ORA TURNO] presso [SEDE] [INDIRIZZO]',
            ['nome' => 'Mario', 'cognome' => 'Rossi', 'giorno' => '04-11-2026', 'orario' => '22:00', 'descrizione' => 'Sede Nord', 'indirizzo' => 'Via Roma 1', 'comune' => 'Milano', 'sigla' => 'MI'],
        );

        $this->assertSame('Ciao Rossi Mario, turno il 04-11-2026 alle 22:00 presso Sede Nord Via Roma 1 Milano (MI)', $testo);
    }

    public function test_destinatari_scarta_gli_indirizzi_non_validi(): void
    {
        $destinatari = (new Comunicazioni)->destinatari('a@example.com, non-valido, , b@example.com');

        $this->assertSame(['a@example.com' => 'Amministrazione', 'b@example.com' => 'Amministrazione'], $destinatari);
    }
}

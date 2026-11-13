<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('tc_messaggi')->where('id', 6)->exists()) {
            return;
        }

        DB::table('tc_messaggi')->insert([
            'id' => 6,
            'nome' => 'Messaggio programmazione settimanale',
            'giorni' => 0,
            'ore' => 0,
            'minuti' => 0,
            'invia_sms' => 'N',
            'invia_email' => 'Y',
            'modello_sms' => '',
            'modello_email' => '<p>Buongiorno</p><p>Qui di seguito la situazione della programmazione della settimana in corso</p>',
            'oggetto' => 'Programmazione',
            'mittente' => 'ItalPol',
            'numero_sms' => 1,
            'numero_email' => 1,
            'admin_sms' => '',
            'admin_email' => 'giovanniflace@gmail.com, alessiosolari22@gmail.com',
        ]);
    }

    public function down(): void
    {
        DB::table('tc_messaggi')->where('id', 6)->where('nome', 'Messaggio programmazione settimanale')->delete();
    }
};

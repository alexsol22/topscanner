<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tc_messaggi')->update(['invia_sms' => 'N']);

        DB::table('tc_messaggi')->where('id', 6)->update([
            'admin_email' => 'giovanniflace@gmail.com, alessiosolari22@gmail.com',
        ]);
    }

    public function down(): void
    {
        //
    }
};

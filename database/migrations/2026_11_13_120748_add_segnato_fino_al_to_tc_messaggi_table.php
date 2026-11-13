<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tc_messaggi', function (Blueprint $table) {
            $table->date('segnato_fino_al')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tc_messaggi', function (Blueprint $table) {
            $table->dropColumn('segnato_fino_al');
        });
    }
};

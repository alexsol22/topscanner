<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tc_send_whatsapp', function (Blueprint $table) {
            $table->increments('id');
            $table->string('destinatario', 20);
            $table->string('tipo', 20);
            $table->string('template', 100)->nullable();
            $table->string('lingua', 12)->nullable();
            $table->text('testo')->nullable();
            $table->text('parametri')->nullable();
            $table->string('wamid', 191)->nullable()->unique();
            $table->string('stato', 20);
            $table->text('errore')->nullable();
            $table->unsignedInteger('id_operatore')->default(0);
            $table->dateTime('data_invio');
            $table->dateTime('aggiornato_il')->nullable();
            $table->index('data_invio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tc_send_whatsapp');
    }
};

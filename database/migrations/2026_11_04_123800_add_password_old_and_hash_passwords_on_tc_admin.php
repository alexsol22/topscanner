<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tc_admin', 'password_old')) {
            Schema::table('tc_admin', function (Blueprint $table) {
                $table->string('password_old', 40)->nullable()->after('password');
            });

            DB::table('tc_admin')->update(['password_old' => DB::raw('password')]);
        }

        Schema::table('tc_admin', function (Blueprint $table) {
            $table->string('password', 255)->nullable()->change();
        });

        DB::table('tc_admin')
            ->whereNotNull('password_old')
            ->where('password_old', '!=', '')
            ->orderBy('id')
            ->each(function (object $utente): void {
                if (str_starts_with((string) $utente->password, '$2y$')) {
                    return;
                }

                DB::table('tc_admin')->where('id', $utente->id)->update(['password' => Hash::make($utente->password_old)]);
            });
    }

    public function down(): void
    {
        DB::table('tc_admin')->whereNotNull('password_old')->update(['password' => DB::raw('password_old')]);

        Schema::table('tc_admin', function (Blueprint $table) {
            $table->string('password', 40)->nullable()->change();
            $table->dropColumn('password_old');
        });
    }
};

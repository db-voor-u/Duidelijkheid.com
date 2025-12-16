<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite ondersteunt geen DROP/ADD FOREIGN KEY op bestaande tabellen
        // Deze migratie wordt alleen uitgevoerd voor MySQL/PostgreSQL
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('contact_messages', function (Blueprint $table) {
            // Drop de oude foreign key constraint als die bestaat
            $table->dropForeign(['handled_by']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            // Voeg de nieuwe foreign key toe naar admins tabel
            $table->foreign('handled_by')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['handled_by']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreign('handled_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_pages', function (Blueprint $table) {
            // Voeg de ontbrekende robots index/follow kolommen toe
            if (!Schema::hasColumn('contact_pages', 'robots_index')) {
                $table->boolean('robots_index')->default(true)->after('canonical_url');
            }
            if (!Schema::hasColumn('contact_pages', 'robots_follow')) {
                $table->boolean('robots_follow')->default(true)->after('robots_index');
            }
            // De rest van de kolommen die de controller probeert te vullen
            if (!Schema::hasColumn('contact_pages', 'published')) {
                $table->boolean('published')->default(true);
            }
            if (!Schema::hasColumn('contact_pages', 'show_form')) {
                $table->boolean('show_form')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_pages', function (Blueprint $table) {
            // Verwijder de kolommen als de migratie wordt teruggedraaid
            if (Schema::hasColumn('contact_pages', 'robots_index')) {
                $table->dropColumn('robots_index');
            }
            if (Schema::hasColumn('contact_pages', 'robots_follow')) {
                $table->dropColumn('robots_follow');
            }
            if (Schema::hasColumn('contact_pages', 'published')) {
                $table->dropColumn('published');
            }
            if (Schema::hasColumn('contact_pages', 'show_form')) {
                $table->dropColumn('show_form');
            }
        });
    }
};

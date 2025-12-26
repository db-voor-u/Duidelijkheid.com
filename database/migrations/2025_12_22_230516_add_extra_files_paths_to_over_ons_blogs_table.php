<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voegt extra_files_paths kolom toe aan over_ons_blogs tabel
     * voor pariteit met de blogs tabel.
     */
    public function up(): void
    {
        Schema::table('over_ons_blogs', function (Blueprint $table) {
            if (! Schema::hasColumn('over_ons_blogs', 'extra_files_paths')) {
                $table->json('extra_files_paths')->nullable()->after('download_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('over_ons_blogs', function (Blueprint $table) {
            if (Schema::hasColumn('over_ons_blogs', 'extra_files_paths')) {
                $table->dropColumn('extra_files_paths');
            }
        });
    }
};

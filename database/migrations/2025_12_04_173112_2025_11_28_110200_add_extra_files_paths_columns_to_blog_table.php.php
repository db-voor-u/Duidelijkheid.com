<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Slaat een JSON array op van paden naar extra bestanden
            if (! Schema::hasColumn('blogs', 'extra_files_paths')) {
                $table->json('extra_files_paths')->nullable()->after('download_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            if (Schema::hasColumn('blogs', 'extra_files_paths')) {
                $table->dropColumn('extra_files_paths');
            }
        });
    }
};

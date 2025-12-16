<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voeg kolommen voor video/media-type en downloadbestand toe aan de blogs tabel.
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {

            // Kolommen voor Video/Hero Media Type (uit 2025_11_30)
            if (! Schema::hasColumn('blogs', 'media_type')) {
                $table->enum('media_type', ['image', 'youtube', 'upload'])->default('image')->after('cover_image_path');
            }
            if (! Schema::hasColumn('blogs', 'video_path')) {
                // Gebruikt voor YouTube URL of pad naar geuploade video
                $table->string('video_path')->nullable()->after('media_type');
            }

            // Kolom voor Download Bestand (uit 2025_12_01)
            if (! Schema::hasColumn('blogs', 'download_file_path')) {
                $table->string('download_file_path')->nullable()->after('video_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Verwijder in omgekeerde volgorde
            if (Schema::hasColumn('blogs', 'download_file_path')) {
                $table->dropColumn('download_file_path');
            }

            if (Schema::hasColumn('blogs', 'video_path')) {
                $table->dropColumn('video_path');
            }
            if (Schema::hasColumn('blogs', 'media_type')) {
                $table->dropColumn('media_type');
            }
        });
    }
};

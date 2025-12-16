<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel voor blogartikelen die alleen op de Over Ons pagina verschijnen.
     */
    public function up(): void
    {
        Schema::create('over_ons_blogs', function (Blueprint $table) {
            $table->id();

            // Content
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');

            // Media
            $table->string('cover_image_path')->nullable();
            $table->enum('media_type', ['image', 'youtube', 'upload'])->default('image');
            $table->string('video_path')->nullable();
            $table->string('download_file_path')->nullable();

            // Status/Publicatie
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            // SEO
            $table->string('meta_title', 70)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->string('og_image_path')->nullable();

            // Tijdstempels
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('over_ons_blogs');
    }
};

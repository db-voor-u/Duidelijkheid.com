<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('over_ons_pages', function (Blueprint $table) {
            $table->id();

            // --- Content / Hero ---
            // 'title' in Vue mapt naar 'hero_title' in de database
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            // 'content' in Vue mapt naar 'intro' in de database
            $table->text('intro')->nullable();
            $table->string('hero_image_path')->nullable();

            // --- SEO ---
            // 'meta_title' in Vue mapt naar 'seo_title'
            $table->string('seo_title')->nullable();
            // 'meta_description' in Vue mapt naar 'seo_description'
            $table->text('seo_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('seo_image_path')->nullable();

            // --- Robots/Status ---
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->boolean('published')->default(true);

            // --- Audit ---
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('over_ons_pages');
    }
};

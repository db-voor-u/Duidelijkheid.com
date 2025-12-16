<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();

            // Content
            $table->string('title', 255);
            $table->string('slug', 191)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content'); // HTML van Quill
            $table->string('cover_image_path', 512)->nullable();

            // Publicatie
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('published_at')->nullable()->index();

            // SEO
            $table->string('meta_title', 70)->nullable();           // advies: ~60–70
            $table->string('meta_description', 160)->nullable();    // advies: ~155–160
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->string('og_image_path', 512)->nullable();

            $table->softDeletes();
            $table->timestamps();

            // handige index voor lijstweergave
            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};

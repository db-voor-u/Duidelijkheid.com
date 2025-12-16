<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_pages', function (Blueprint $table) {
            $table->id();

            // Hero / content
            $table->string('hero_title', 160)->default('Contact');
            $table->text('hero_subtitle')->nullable();
            $table->text('intro')->nullable();
            $table->string('hero_image_path')->nullable();

            // Form instellingen
            $table->boolean('show_form')->default(true);
            $table->string('recipient_email', 160)->nullable();       // waarheen versturen
            $table->json('additional_recipients')->nullable();        // {cc:[], bcc:[]}

            // SEO
            $table->string('seo_title', 160)->nullable();
            $table->text('seo_description')->nullable();

            // Publicatie
            $table->boolean('published')->default(true);

            // Audit (optioneel, geen FK verplicht)
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            // Handige indexen
            $table->index(['published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_pages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('welcomes', function (Blueprint $table) {
            $table->string('meta_title', 65)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('seo_image_path')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('welcomes', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title','meta_description','seo_image_path',
                'canonical_url','robots_index','robots_follow'
            ]);
        });
    }
};

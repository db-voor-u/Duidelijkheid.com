<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * GECONSOLIDEERD PAGES SCHEMA
 * 
 * Deze migratie voegt ontbrekende kolommen toe aan bestaande tabellen
 * zodat ze een consistent schema hebben. Dit voorkomt toekomstige
 * losse "add_x_to_y" migraties.
 * 
 * Tabellen die worden geconsolideerd:
 * - blog_pages: hero content voor blog overzicht
 * - innovatie_pages: hero content voor innovatie sectie
 * - over_ons_pages: hero content voor over ons sectie
 * - contact_pages: hero content + form settings voor contact
 */
return new class extends Migration {
    public function up(): void
    {
        // ============================================
        // 1. BLOG_PAGES - Standaardiseer velden
        // ============================================
        Schema::table('blog_pages', function (Blueprint $table) {
            // Voeg ontbrekende velden toe voor consistentie
            if (!Schema::hasColumn('blog_pages', 'hero_title')) {
                $table->string('hero_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('blog_pages', 'hero_subtitle')) {
                $table->text('hero_subtitle')->nullable()->after('hero_title');
            }
            if (!Schema::hasColumn('blog_pages', 'intro')) {
                $table->text('intro')->nullable()->after('hero_subtitle');
            }
            if (!Schema::hasColumn('blog_pages', 'hero_image_path')) {
                $table->string('hero_image_path')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('blog_pages', 'published')) {
                $table->boolean('published')->default(true)->after('robots_follow');
            }
            if (!Schema::hasColumn('blog_pages', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });

        // ============================================
        // 2. INNOVATIE_PAGES - Standaardiseer velden
        // ============================================
        Schema::table('innovatie_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('innovatie_pages', 'hero_title')) {
                $table->string('hero_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('innovatie_pages', 'hero_subtitle')) {
                $table->text('hero_subtitle')->nullable()->after('hero_title');
            }
            if (!Schema::hasColumn('innovatie_pages', 'intro')) {
                $table->text('intro')->nullable()->after('content');
            }
            if (!Schema::hasColumn('innovatie_pages', 'hero_image_path')) {
                $table->string('hero_image_path')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('innovatie_pages', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });

        // ============================================
        // 3. OVER_ONS_PAGES - Voeg content kolom toe
        // ============================================
        Schema::table('over_ons_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('over_ons_pages', 'content')) {
                $table->longText('content')->nullable()->after('intro');
            }
            if (!Schema::hasColumn('over_ons_pages', 'title')) {
                $table->string('title')->nullable()->after('id');
            }
            if (!Schema::hasColumn('over_ons_pages', 'image_path')) {
                $table->string('image_path')->nullable()->after('hero_image_path');
            }
            // SEO standaardisatie - voeg meta_ prefixed velden toe indien ontbreken
            if (!Schema::hasColumn('over_ons_pages', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('seo_image_path');
            }
            if (!Schema::hasColumn('over_ons_pages', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        // ============================================
        // 4. CONTACT_PAGES - Voeg ontbrekende SEO velden toe
        // ============================================
        Schema::table('contact_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_pages', 'title')) {
                $table->string('title')->nullable()->after('id');
            }
            if (!Schema::hasColumn('contact_pages', 'content')) {
                $table->longText('content')->nullable()->after('intro');
            }
            if (!Schema::hasColumn('contact_pages', 'image_path')) {
                $table->string('image_path')->nullable()->after('hero_image_path');
            }
            if (!Schema::hasColumn('contact_pages', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('seo_description');
            }
            if (!Schema::hasColumn('contact_pages', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('contact_pages', 'canonical_url')) {
                $table->string('canonical_url')->nullable()->after('meta_description');
            }
            if (!Schema::hasColumn('contact_pages', 'seo_image_path')) {
                $table->string('seo_image_path')->nullable()->after('canonical_url');
            }
        });

        // ============================================
        // 5. BLOGS - Zorg dat alle media velden aanwezig zijn
        // ============================================
        Schema::table('blogs', function (Blueprint $table) {
            // URL veld voor externe links
            if (!Schema::hasColumn('blogs', 'external_url')) {
                $table->string('external_url', 2048)->nullable()->after('extra_files_paths');
            }
        });

        // ============================================
        // 6. OVER_ONS_BLOGS - Breng in lijn met blogs tabel
        // ============================================
        // (extra_files_paths is al toegevoegd in aparte migratie)
        Schema::table('over_ons_blogs', function (Blueprint $table) {
            if (!Schema::hasColumn('over_ons_blogs', 'external_url')) {
                $table->string('external_url', 2048)->nullable()->after('download_file_path');
            }
        });
    }

    public function down(): void
    {
        // Blog pages
        Schema::table('blog_pages', function (Blueprint $table) {
            $columns = ['hero_title', 'hero_subtitle', 'intro', 'hero_image_path', 'published', 'updated_by'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('blog_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Innovatie pages
        Schema::table('innovatie_pages', function (Blueprint $table) {
            $columns = ['hero_title', 'hero_subtitle', 'intro', 'hero_image_path', 'updated_by'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('innovatie_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Over ons pages
        Schema::table('over_ons_pages', function (Blueprint $table) {
            $columns = ['content', 'title', 'image_path', 'meta_title', 'meta_description'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('over_ons_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Contact pages
        Schema::table('contact_pages', function (Blueprint $table) {
            $columns = ['title', 'content', 'image_path', 'meta_title', 'meta_description', 'canonical_url', 'seo_image_path'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('contact_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Blogs
        Schema::table('blogs', function (Blueprint $table) {
            if (Schema::hasColumn('blogs', 'external_url')) {
                $table->dropColumn('external_url');
            }
        });

        // Over ons blogs
        Schema::table('over_ons_blogs', function (Blueprint $table) {
            if (Schema::hasColumn('over_ons_blogs', 'external_url')) {
                $table->dropColumn('external_url');
            }
        });
    }
};

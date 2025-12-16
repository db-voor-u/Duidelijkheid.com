<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Zorg ervoor dat er 'blog' categorieën zijn als er nog geen geschikte categorieën zijn
        // Dit is nodig omdat 'type' mogelijk leeg is of alleen 'innovatie'/'over_ons' bestaat.

        $hasBlogCats = DB::table('categories')
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereNotIn('type', ['innovatie', 'over_ons']);
            })
            ->exists();

        if (!$hasBlogCats) {
            // Maak een algemene categorie aan
            DB::table('categories')->insert([
                'name' => 'Algemeen',
                'slug' => 'algemeen',
                'color' => 'bg-blue-500',
                'type' => 'blog',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Zorg ook voor een Over Ons categorie
        $hasOverOns = DB::table('categories')->where('type', 'over_ons')->exists();
        if (!$hasOverOns) {
            DB::table('categories')->insert([
                'name' => 'Over Duidelijkheid',
                'slug' => 'over-duidelijkheid',
                'color' => 'bg-green-500',
                'type' => 'over_ons',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Zorg voor Innovatie
        $hasInnovatie = DB::table('categories')->where('type', 'innovatie')->exists();
        if (!$hasInnovatie) {
            DB::table('categories')->insert([
                'name' => 'Nieuwe Tech',
                'slug' => 'nieuwe-tech',
                'color' => 'bg-purple-500',
                'type' => 'innovatie',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update posts die geen categorie hebben naar de algemene categorie
        $defaultCatId = DB::table('categories')->where('slug', 'algemeen')->value('id');
        if ($defaultCatId) {
            DB::table('blogs')->whereNull('category_id')->update(['category_id' => $defaultCatId]);
        }
    }

    public function down(): void
    {
        // Geen down nodig, data seeding
    }
};

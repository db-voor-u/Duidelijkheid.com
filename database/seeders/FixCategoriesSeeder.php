<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Blog;

class FixCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Algemeen / Blog (Type NULL of 'blog')
        $blogCat = Category::where(function ($q) {
            $q->whereNull('type')
                ->orWhereNotIn('type', ['innovatie', 'over_ons']);
        })->first();

        if (!$blogCat) {
            $blogCat = Category::create([
                'name' => 'Algemeen',
                'slug' => 'algemeen',
                'color' => 'bg-blue-500', // Zorg dat dit een geldige Tailwind class is
                'type' => 'blog'
            ]);
            $this->command->info('Category "Algemeen" created.');
        }

        // 2. Over Ons
        $overOnsCat = Category::where('type', 'over_ons')->first();
        if (!$overOnsCat) {
            $overOnsCat = Category::create([
                'name' => 'Over Duidelijkheid',
                'slug' => 'over-duidelijkheid',
                'color' => 'bg-green-500',
                'type' => 'over_ons'
            ]);
            $this->command->info('Category "Over Duidelijkheid" created.');
        }

        // 3. Innovatie
        $innovatieCat = Category::where('type', 'innovatie')->first();
        if (!$innovatieCat) {
            $innovatieCat = Category::create([
                'name' => 'Nieuwe Tech',
                'slug' => 'nieuwe-tech',
                'color' => 'bg-purple-500',
                'type' => 'innovatie'
            ]);
            $this->command->info('Category "Nieuwe Tech" created.');
        }

        // 4. Update Orphaned Blogs
        // Assign to 'Algemeen' if no category
        $count = Blog::whereNull('category_id')->update(['category_id' => $blogCat->id]);
        if ($count > 0) {
            $this->command->info("$count blogs updated to category 'Algemeen'.");
        }
    }
}

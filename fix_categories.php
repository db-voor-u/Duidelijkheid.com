<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cats = App\Models\Category::all();

if ($cats->count() === 0) {
    echo "No categories found. Creating defaults...\n";
    App\Models\Category::create(['name' => 'Algemeen', 'slug' => 'algemeen', 'color' => 'bg-gray-500', 'type' => 'blog']);

    // Also update existing blogs to use this category if they have none
    $blogCat = App\Models\Category::first();
    App\Models\Blog::whereNull('category_id')->update(['category_id' => $blogCat->id]);

    echo "Created 'Algemeen' category and updated orphaned blogs.\n";
} else {
    echo "Categories found: " . $cats->count() . "\n";
    foreach ($cats as $c) {
        echo "- " . $c->name . " (" . $c->type . ")\n";
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image_path',

        // 👇 MEDIA VELDEN TOEGEVOEGD
        'media_type',
        'video_path',

        // 👇 DOWNLOAD VELD TOEGEVOEGD
        'download_file_path',
        // 👇 NIEUW: Voor de lijst met extra bestanden
        'extra_files_paths',

        'category_id', // 👇 Categorie FK

        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots_index',
        'robots_follow',
        'og_image_path',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'published_at' => 'datetime',
        // 'media_type', 'video_path', en 'download_file_path' zijn strings/enums en hebben geen extra cast nodig.
        // 👇 NIEUW: Zorg dat de JSON kolom als array wordt gelezen
        'extra_files_paths' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }



    // Gebruik slugs in routes (optioneel)
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true)
            ->whereNotNull('published_at');
    }

    // Helper: maak een slug (kan in controller/service gebruikt worden)
    public static function makeSlug(string $title): string
    {
        $slug = Str::slug($title);
        // eenvoudige collision-afhandeling buiten scope; doe uniek-check in controller
        return $slug;
    }

    // Handige fallback in views
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

}

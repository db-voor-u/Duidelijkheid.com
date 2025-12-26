<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OverOnsBlog extends Model
{
    use SoftDeletes;

    protected $table = 'over_ons_blogs';

    protected $fillable = [
        // Content
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image_path',

        // Media
        'media_type',
        'video_path',
        'download_file_path',
        'extra_files_paths',
        'external_url',      // Externe link (nieuw toegevoegd)

        // Relaties
        'category_id',

        // Publicatie
        'is_published',
        'published_at',

        // SEO
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
        'extra_files_paths' => 'array', // JSON kolom als array
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true)
            ->whereNotNull('published_at');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

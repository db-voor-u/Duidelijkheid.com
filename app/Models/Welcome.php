<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Welcome extends Model
{
    protected $fillable = [
        'title', 'image_path', 'content',
        'meta_title', 'meta_description', 'seo_image_path',
        'canonical_url', 'robots_index', 'robots_follow',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
    ];
}

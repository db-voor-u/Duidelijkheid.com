<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPage extends Model
{
    protected $fillable = [
        'title','content','image_path',
        'meta_title','meta_description','seo_image_path',
        'canonical_url','robots_index','robots_follow',
    ];
}

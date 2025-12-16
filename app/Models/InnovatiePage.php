<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InnovatiePage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
    ];
}

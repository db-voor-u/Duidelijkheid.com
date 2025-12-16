<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','email','phone','subject','message','consent',
        'status','handled_by','read_at','replied_at','archived_at',
        'hp_filled','duration_ms','is_spam','spam_reason','dedupe_hash',
        'ip','user_agent','referer','utm','locale','source','user_id',
        // Verwijder deze als je géén losse kolommen hebt:
        // 'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
    ];

    protected $casts = [
        'consent'     => 'bool',
        'hp_filled'   => 'bool',
        'is_spam'     => 'bool',
        'utm'         => 'array',   // json/jsonb
        'read_at'     => 'datetime',
        'replied_at'  => 'datetime',
        'archived_at' => 'datetime',
    ];

    // Scopes ...

    public function archive(): void
    {
        $this->archived_at = now();
        // Laat status ongemoeid; je admin-controller en statistieken kijken naar archived_at.
        $this->save();
    }

    public function user()   { return $this->belongsTo(User::class); }
    public function handler(){ return $this->belongsTo(Admin::class, 'handled_by'); }
}

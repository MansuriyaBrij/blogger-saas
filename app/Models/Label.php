<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Label extends Model
{
    protected $fillable = [
        'user_id',
        'blogger_account_id',
        'name',
        'post_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bloggerAccount(): BelongsTo
    {
        return $this->belongsTo(BloggerAccount::class);
    }
}

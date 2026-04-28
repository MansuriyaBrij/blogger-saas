<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'blogger_account_id',
        'blogger_post_id',
        'title',
        'content',
        'url',
        'labels',
        'status',
        'published_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'published_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bloggerAccount(): BelongsTo
    {
        return $this->belongsTo(BloggerAccount::class);
    }
}

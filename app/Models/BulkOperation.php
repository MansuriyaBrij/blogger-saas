<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkOperation extends Model
{
    protected $fillable = [
        'user_id',
        'blogger_account_id',
        'type',
        'total',
        'success_count',
        'failed_count',
        'error_log',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'error_log'    => 'array',
            'completed_at' => 'datetime',
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

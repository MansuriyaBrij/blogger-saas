<?php

namespace App\Events;

use App\Models\BloggerAccount;
use App\Models\Label;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public BloggerAccount $bloggerAccount,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->user->id);
    }

    public function broadcastWith(): array
    {
        return [
            'blog_name' => $this->bloggerAccount->blog_name,
            'total_posts' => Post::where('blogger_account_id', $this->bloggerAccount->id)->count(),
            'total_labels' => Label::where('blogger_account_id', $this->bloggerAccount->id)->count(),
            'synced_at' => $this->bloggerAccount->last_synced_at?->toISOString(),
        ];
    }
}

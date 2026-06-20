<?php

namespace App\Notifications;

use App\Models\BloggerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BlogSynced extends Notification
{
    use Queueable;

    public function __construct(
        public BloggerAccount $bloggerAccount,
        public int $postCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => 'blog_synced',
            'title'     => 'Blog synced',
            'body'      => "\"{$this->bloggerAccount->blog_name}\" synced — {$this->postCount} posts.",
            'url'       => '/blogs',
            'data'      => [
                'blog_id'   => $this->bloggerAccount->id,
                'blog_name' => $this->bloggerAccount->blog_name,
                'post_count' => $this->postCount,
                'synced_at' => $this->bloggerAccount->last_synced_at?->toISOString(),
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}

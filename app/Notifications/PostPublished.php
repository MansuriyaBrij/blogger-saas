<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PostPublished extends Notification
{
    use Queueable;

    public function __construct(public Post $post) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'  => 'post_published',
            'title' => 'Post published',
            'body'  => "\"{$this->post->title}\" is now live.",
            'url'   => "/posts/{$this->post->id}",
            'data'  => [
                'post_id'      => $this->post->id,
                'post_title'   => $this->post->title,
                'blog_name'    => $this->post->bloggerAccount?->blog_name,
                'published_at' => $this->post->published_at?->toISOString(),
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}

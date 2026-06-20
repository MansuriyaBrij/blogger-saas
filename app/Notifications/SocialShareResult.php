<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SocialShareResult extends Notification
{
    use Queueable;

    public function __construct(
        public int $postId,
        public string $postTitle,
        public string $platform,
        public string $status,  // 'success' | 'failed'
        public ?string $message = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $succeeded = $this->status === 'success';

        return [
            'type'  => 'social_share_result',
            'title' => $succeeded ? 'Post shared' : 'Share failed',
            'body'  => $succeeded
                ? "\"{$this->postTitle}\" was shared to " . ucfirst($this->platform) . '.'
                : "Sharing \"{$this->postTitle}\" to " . ucfirst($this->platform) . " failed: {$this->message}",
            'url'   => "/posts/{$this->postId}",
            'data'  => [
                'post_id'  => $this->postId,
                'platform' => $this->platform,
                'status'   => $this->status,
                'message'  => $this->message,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}

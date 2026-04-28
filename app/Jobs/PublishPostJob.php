<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\BloggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Post $post) {}

    public function handle(): void
    {
        $account = $this->post->bloggerAccount;

        app()->makeWith(BloggerService::class, ['user' => $this->post->user])
            ->publishPost($account->blog_id, $this->post->blogger_post_id);

        $this->post->update(['status' => 'LIVE']);
    }
}

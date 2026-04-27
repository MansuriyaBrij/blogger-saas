<?php

namespace App\Jobs;

use App\Events\SyncCompleted;
use App\Models\BloggerAccount;
use App\Models\Label;
use App\Models\Post;
use App\Models\User;
use App\Services\BloggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBlogPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public User $user,
        public BloggerAccount $bloggerAccount,
    ) {}

    public function handle(): void
    {
        $service = app()->makeWith(BloggerService::class, ['user' => $this->user]);
        $posts = $service->getPosts($this->bloggerAccount->blog_id);

        $labelCounts = [];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                [
                    'blogger_account_id' => $this->bloggerAccount->id,
                    'blogger_post_id' => $post['id'],
                ],
                [
                    'user_id' => $this->user->id,
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'url' => $post['url'],
                    'labels' => $post['labels'],
                    'status' => $post['status'],
                    'published_at' => $post['published'] ? new \DateTime($post['published']) : null,
                    'synced_at' => now(),
                ]
            );

            foreach ($post['labels'] as $labelName) {
                $labelCounts[$labelName] = ($labelCounts[$labelName] ?? 0) + 1;
            }
        }

        foreach ($labelCounts as $name => $count) {
            Label::updateOrCreate(
                [
                    'blogger_account_id' => $this->bloggerAccount->id,
                    'name' => $name,
                ],
                [
                    'user_id' => $this->user->id,
                    'post_count' => $count,
                ]
            );
        }

        $this->bloggerAccount->update(['last_synced_at' => now()]);

        broadcast(new SyncCompleted($this->user, $this->bloggerAccount));
    }
}

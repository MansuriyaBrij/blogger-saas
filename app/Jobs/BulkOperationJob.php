<?php

namespace App\Jobs;

use App\Events\BulkOperationCompleted;
use App\Models\BulkOperation;
use App\Models\Post;
use App\Services\BloggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BulkOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public BulkOperation $bulkOperation,
        public array $postIds,
        public ?string $labelName = null,
    ) {}

    public function handle(): void
    {
        $this->bulkOperation->update(['status' => 'running']);

        $success = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($this->postIds as $postId) {
            try {
                $post    = Post::findOrFail($postId);
                $user    = $post->user;
                $account = $post->bloggerAccount;
                $service = app()->makeWith(BloggerService::class, ['user' => $user]);

                match ($this->bulkOperation->type) {
                    'publish'      => $this->publish($service, $post, $account),
                    'draft'        => $this->revertToDraft($service, $post, $account),
                    'delete'       => $this->delete($service, $post, $account),
                    'label_add'    => $this->addLabel($post),
                    'label_remove' => $this->removeLabel($post),
                };

                $success++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "Post {$postId}: " . $e->getMessage();
            }
        }

        $this->bulkOperation->update([
            'status'        => 'done',
            'success_count' => $success,
            'failed_count'  => $failed,
            'error_log'     => $errors,
            'completed_at'  => now(),
        ]);

        broadcast(new BulkOperationCompleted($this->bulkOperation));

        $this->bulkOperation->user->notify(
            new \App\Notifications\BulkOperationDoneNotification($this->bulkOperation)
        );
    }

    private function publish(BloggerService $service, Post $post, $account): void
    {
        $service->publishPost($account->blog_id, $post->blogger_post_id);
        $post->update(['status' => 'LIVE']);
    }

    private function revertToDraft(BloggerService $service, Post $post, $account): void
    {
        $service->revertToDraft($account->blog_id, $post->blogger_post_id);
        $post->update(['status' => 'DRAFT']);
    }

    private function delete(BloggerService $service, Post $post, $account): void
    {
        $service->deletePost($account->blog_id, $post->blogger_post_id);
        $post->delete();
    }

    private function addLabel(Post $post): void
    {
        $labels = $post->labels ?? [];
        if (!in_array($this->labelName, $labels)) {
            $labels[] = $this->labelName;
            $post->update(['labels' => $labels]);
            dispatch(new UpdatePostOnBloggerJob($post));
        }
    }

    private function removeLabel(Post $post): void
    {
        $labels = array_values(array_filter($post->labels ?? [], fn ($l) => $l !== $this->labelName));
        $post->update(['labels' => $labels]);
        dispatch(new UpdatePostOnBloggerJob($post));
    }
}

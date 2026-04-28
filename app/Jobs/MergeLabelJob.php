<?php

namespace App\Jobs;

use App\Models\Label;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MergeLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Label $source,
        public Label $target,
    ) {}

    public function handle(): void
    {
        $posts = Post::where('blogger_account_id', $this->source->blogger_account_id)
            ->whereJsonContains('labels', $this->source->name)
            ->get();

        foreach ($posts as $post) {
            $labels = $post->labels ?? [];

            if (!in_array($this->target->name, $labels)) {
                $labels[] = $this->target->name;
            }

            $labels = array_values(array_filter($labels, fn ($l) => $l !== $this->source->name));
            $post->update(['labels' => $labels]);
            dispatch(new UpdatePostOnBloggerJob($post));
        }

        $newCount = Post::where('blogger_account_id', $this->target->blogger_account_id)
            ->whereJsonContains('labels', $this->target->name)
            ->count();

        $this->target->update(['post_count' => $newCount]);
        $this->source->delete();
    }
}

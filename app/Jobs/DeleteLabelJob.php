<?php

namespace App\Jobs;

use App\Models\Label;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Label $label) {}

    public function handle(): void
    {
        $posts = Post::where('blogger_account_id', $this->label->blogger_account_id)
            ->whereJsonContains('labels', $this->label->name)
            ->get();

        foreach ($posts as $post) {
            $labels = array_values(array_filter($post->labels ?? [], fn ($l) => $l !== $this->label->name));
            $post->update(['labels' => $labels]);
            dispatch(new UpdatePostOnBloggerJob($post));
        }

        $this->label->delete();
    }
}

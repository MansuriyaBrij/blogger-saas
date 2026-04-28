<?php

namespace App\Jobs;

use App\Models\Label;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenameLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Label $label,
        public string $newName,
    ) {}

    public function handle(): void
    {
        $oldName = $this->label->name;

        $posts = Post::where('blogger_account_id', $this->label->blogger_account_id)
            ->whereJsonContains('labels', $oldName)
            ->get();

        foreach ($posts as $post) {
            $updated = array_map(
                fn ($l) => $l === $oldName ? $this->newName : $l,
                $post->labels ?? []
            );
            $post->update(['labels' => $updated]);
            dispatch(new UpdatePostOnBloggerJob($post));
        }

        $this->label->update(['name' => $this->newName]);
    }
}

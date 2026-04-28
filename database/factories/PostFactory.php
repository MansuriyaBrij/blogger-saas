<?php

namespace Database\Factories;

use App\Models\BloggerAccount;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'blogger_account_id' => BloggerAccount::factory(),
            'blogger_post_id'    => 'post-' . fake()->unique()->numerify('######'),
            'title'              => fake()->sentence(6),
            'content'            => '<p>' . fake()->paragraphs(3, true) . '</p>',
            'url'                => fake()->url(),
            'labels'             => fake()->randomElements(['php', 'laravel', 'mysql', 'vue', 'react'], rand(0, 3)),
            'status'             => fake()->randomElement(['LIVE', 'DRAFT', 'SCHEDULED']),
            'published_at'       => fake()->dateTimeBetween('-2 years', 'now'),
            'synced_at'          => now(),
        ];
    }
}

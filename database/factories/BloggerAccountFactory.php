<?php

namespace Database\Factories;

use App\Models\BloggerAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloggerAccount>
 */
class BloggerAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'blog_id' => 'blog-' . fake()->unique()->numerify('######'),
            'blog_name' => fake()->words(3, true),
            'blog_url' => 'https://' . fake()->slug() . '.blogspot.com',
            'is_active' => true,
            'last_synced_at' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\BloggerAccount;
use App\Models\Label;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Label>
 */
class LabelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'blogger_account_id' => BloggerAccount::factory(),
            'name'               => fake()->unique()->word(),
            'post_count'         => fake()->numberBetween(1, 50),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'is_public' => true,
            'avatar_image' => null,
            'avatar_cloudflare_id' => null,
            'banner_image' => null,
            'banner_cloudflare_id' => null,
        ];
    }

    /**
     * Indicate that the channel is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
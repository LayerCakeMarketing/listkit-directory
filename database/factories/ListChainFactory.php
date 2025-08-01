<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ListChain>
 */
class ListChainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'owner_type' => User::class,
            'owner_id' => User::factory(),
            'visibility' => 'public',
            'status' => 'published',
            'cover_image' => null,
            'cover_cloudflare_id' => null,
            'views_count' => 0,
            'lists_count' => 0,
            'metadata' => null,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the chain is owned by a channel.
     */
    public function forChannel(Channel $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_type' => Channel::class,
            'owner_id' => $channel->id,
        ]);
    }

    /**
     * Indicate that the chain is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    /**
     * Indicate that the chain is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
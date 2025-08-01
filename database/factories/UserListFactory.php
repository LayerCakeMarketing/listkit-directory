<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserList>
 */
class UserListFactory extends Factory
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
            'user_id' => function (array $attributes) {
                return $attributes['owner_type'] === User::class 
                    ? $attributes['owner_id'] 
                    : User::factory();
            },
            'channel_id' => null,
            'visibility' => 'public',
            'status' => 'active',
            'is_draft' => false,
            'featured_image' => null,
            'featured_image_cloudflare_id' => null,
        ];
    }

    /**
     * Indicate that the list is owned by a channel.
     */
    public function forChannel(Channel $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_type' => Channel::class,
            'owner_id' => $channel->id,
            'user_id' => $channel->user_id,
            'channel_id' => $channel->id,
        ]);
    }

    /**
     * Indicate that the list is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    /**
     * Indicate that the list is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_draft' => true,
        ]);
    }
}
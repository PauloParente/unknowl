<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chat>
 */
class ChatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => null,
            'description' => null,
            'type' => 'private',
            'created_by' => \App\Models\User::factory(),
            'last_message_at' => null,
        ];
    }

    /**
     * Indicate that the chat is a group chat.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the chat is a private chat.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'private',
            'name' => null,
            'description' => null,
        ]);
    }

    /**
     * Indicate that the chat has recent activity.
     */
    public function withRecentActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the chat has old activity.
     */
    public function withOldActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}

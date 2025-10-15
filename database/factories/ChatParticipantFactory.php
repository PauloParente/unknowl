<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatParticipant>
 */
class ChatParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => \App\Models\Chat::factory(),
            'user_id' => \App\Models\User::factory(),
            'role' => 'member',
            'joined_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'last_read_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'is_muted' => false,
            'is_archived' => false,
        ];
    }

    /**
     * Indicate that the participant is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the participant is a moderator.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
        ]);
    }

    /**
     * Indicate that the participant is a member.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
        ]);
    }

    /**
     * Indicate that the participant is muted.
     */
    public function muted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_muted' => true,
        ]);
    }

    /**
     * Indicate that the participant is not muted.
     */
    public function unmuted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_muted' => false,
        ]);
    }

    /**
     * Indicate that the participant has archived the chat.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }

    /**
     * Indicate that the participant has not archived the chat.
     */
    public function unarchived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => false,
        ]);
    }

    /**
     * Indicate that the participant has unread messages.
     */
    public function withUnreadMessages(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_read_at' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    /**
     * Indicate that the participant has read all messages.
     */
    public function withAllRead(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_read_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the participant has never read messages.
     */
    public function withNeverRead(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_read_at' => null,
        ]);
    }
}

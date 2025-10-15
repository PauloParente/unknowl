<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageRead>
 */
class MessageReadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => \App\Models\Message::factory(),
            'user_id' => \App\Models\User::factory(),
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the message was read recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the message was read a long time ago.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}

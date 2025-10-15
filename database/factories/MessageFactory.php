<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
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
            'content' => fake()->sentence(),
            'type' => 'text',
            'metadata' => null,
            'reply_to' => null,
            'is_edited' => false,
            'edited_at' => null,
        ];
    }

    /**
     * Indicate that the message is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'content' => 'Imagem enviada',
            'metadata' => [
                'filename' => fake()->word() . '.jpg',
                'size' => fake()->numberBetween(1000, 5000000),
                'mime_type' => 'image/jpeg',
            ],
        ]);
    }

    /**
     * Indicate that the message is a file.
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'content' => 'Arquivo enviado',
            'metadata' => [
                'filename' => fake()->word() . '.pdf',
                'size' => fake()->numberBetween(10000, 10000000),
                'mime_type' => 'application/pdf',
            ],
        ]);
    }

    /**
     * Indicate that the message is a system message.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system',
            'content' => fake()->randomElement([
                'Usuário entrou no chat',
                'Usuário saiu do chat',
                'Chat criado',
                'Nome do chat alterado',
            ]),
            'user_id' => null, // System messages don't have a user
        ]);
    }

    /**
     * Indicate that the message is a reply to another message.
     */
    public function reply(): static
    {
        return $this->state(fn (array $attributes) => [
            'reply_to' => \App\Models\Message::factory(),
        ]);
    }

    /**
     * Indicate that the message has been edited.
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_edited' => true,
            'edited_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the message is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the message is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}

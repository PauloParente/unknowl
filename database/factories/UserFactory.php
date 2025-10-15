<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => null, // Sistema atual usa iniciais como fallback
            'cover_url' => null, // Sistema atual usa placeholder genérico
            'bio' => fake('pt_BR')->optional(0.6)->realTextBetween(80, 180),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Usuário com biografia garantida.
     */
    public function withBio(): static
    {
        return $this->state(fn () => [
            'bio' => fake('pt_BR')->realTextBetween(120, 200),
        ]);
    }

    /**
     * Usuário administrador com email previsível.
     */
    public function admin(string $email = 'admin@example.com'): static
    {
        return $this->state(fn () => [
            'name' => 'Administrador',
            'email' => $email,
            'password' => static::$password ??= Hash::make('password'),
        ]);
    }
}

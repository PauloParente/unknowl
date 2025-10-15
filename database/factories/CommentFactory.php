<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'text' => $this->faker->sentences(random_int(1, 3), true),
            'score' => $this->faker->numberBetween(-5, 100),
        ];
    }
}



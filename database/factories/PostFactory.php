<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => rtrim($this->faker->sentence(), '.'),
            'text' => $this->faker->optional(0.7)->paragraphs(random_int(1, 4), true),
            'image_url' => $this->faker->optional(0.3)->passthrough('/images/placeholders/post-image.svg'),
            'score' => $this->faker->numberBetween(-10, 500),
            'is_pinned' => $this->faker->boolean(5), // 5% chance de ser fixado
            'pinned_at' => function (array $attributes) {
                return $attributes['is_pinned'] ? $this->faker->dateTimeBetween('-30 days', 'now') : null;
            },
        ];
    }
}



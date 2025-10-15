<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Community>
 */
class CommunityFactory extends Factory
{
    protected $model = Community::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->slug(2);
        
        return [
            'name' => $name,
            'title' => ucfirst(str_replace('-', ' ', $name)),
            'description' => $this->faker->optional()->paragraph(),
            'avatar' => null, // Sistema atual usa iniciais como fallback
            'cover_url' => null, // Sistema atual usa placeholder genérico
            'rules' => $this->faker->optional(0.8)->randomElements([
                'Seja respeitoso. Ataques pessoais não serão tolerados.',
                'Sem spam ou autopromoção excessiva.',
                'Use títulos claros e descritivos.',
                'Conteúdo fora do tema será removido.',
                'Mantenha discussões construtivas.',
                'Respeite a privacidade de outros usuários.',
                'Não compartilhe informações pessoais.',
                'Use a busca antes de fazer perguntas repetitivas.',
            ], random_int(3, 6)),
            'owner_id' => User::factory(),
            'is_public' => $this->faker->boolean(85), // 85% públicas
            'requires_approval' => $this->faker->boolean(20), // 20% requerem aprovação
            'member_count' => $this->faker->numberBetween(1, 1000),
            'last_activity_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}




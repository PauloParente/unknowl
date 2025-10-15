<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Models\PostVote;
use App\Models\CommentVote;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seed do banco de dados (coerente)...');

        DB::transaction(function () {
            // 1) Usuários: inclui um admin conhecido
            $this->command->info('👥 Criando usuários...');
            $admin = User::factory()->admin('admin@myapp.test')->create([
                'email_verified_at' => now(),
            ]);
            $users = User::factory(49)->withBio()->create();
            $users = $users->prepend($admin);
        $this->command->info('✅ Usuários criados!');

            // 2) Comunidades temáticas
            $this->command->info('🏘️ Criando comunidades temáticas...');
            $topics = [
                ['name' => 'laravel', 'title' => 'Laravel e PHP', 'rules' => ['Seja respeitoso', 'Compartilhe código formatado', 'Sem spam']],
                ['name' => 'vue', 'title' => 'Vue.js e Frontend', 'rules' => ['Use títulos claros', 'Prefira exemplos reprodutíveis', 'Sem ofensas']],
                ['name' => 'futebol', 'title' => 'Futebol Brasil', 'rules' => ['Respeite torcidas', 'Sem conteúdo ofensivo', 'Sem links piratas']],
                ['name' => 'filmes', 'title' => 'Filmes e Séries', 'rules' => ['Use spoiler tag', 'Respeite opiniões', 'Sem pirataria']],
                ['name' => 'animes', 'title' => 'Animes e Mangás', 'rules' => ['Marque spoilers', 'Evite off-topic', 'Sem scans ilegais']],
                ['name' => 'tecnologia', 'title' => 'Tecnologia e Gadgets', 'rules' => ['Fonte das notícias', 'Sem FUD', 'Debata ideias']],
                ['name' => 'javascript', 'title' => 'JavaScript Moderno', 'rules' => ['Cite versões e runtimes', 'Exemplos mínimos e reprodutíveis', 'Sem flamewar']],
                ['name' => 'python', 'title' => 'Python e Ecossistema', 'rules' => ['Indique versão do Python', 'Mostre traceback quando houver', 'Use virtualenv/poetry quando possível']],
                ['name' => 'games', 'title' => 'Games e Consoles', 'rules' => ['Sem spoilers sem aviso', 'Respeite preferências de plataformas', 'Nada de pirataria']],
                ['name' => 'musica', 'title' => 'Música e Produção Musical', 'rules' => ['Créditos aos artistas', 'Nada de leaks', 'Feedback construtivo']],
            ];

            $communities = collect($topics)->map(function ($t) use ($users) {
                $owner = $users->random();
                return Community::factory()->create([
                    'name' => Str::slug($t['name']),
                    'title' => $t['title'],
                    'rules' => $t['rules'],
                    'owner_id' => $owner->id,
                    'is_public' => true,
                    'requires_approval' => fake()->boolean(20),
                    'member_count' => 0,
                    'last_activity_at' => now()->subDays(random_int(0, 20)),
                    'created_at' => now()->subDays(random_int(20, 90)),
                    'updated_at' => now()->subDays(random_int(0, 10)),
                ]);
            });
        $this->command->info('✅ Comunidades criadas!');

            // 3) Membros e moderadores
            $this->command->info('🔗 Associando membros e moderadores...');
        foreach ($communities as $community) {
                $memberCount = random_int(15, 40);
                $members = $users->random($memberCount)->pluck('id')->toArray();
                $community->members()->sync($members);

                // Moderadores: 1-3 por comunidade (dentre os membros)
                $moderatorIds = collect($members)->shuffle()->take(random_int(1, 3));
                foreach ($moderatorIds as $modId) {
                    DB::table('community_moderators')->insert([
                        'community_id' => $community->id,
                        'user_id' => $modId,
                        'role' => 'moderator',
                        'assigned_by' => $community->owner_id,
                        'assigned_at' => now()->subDays(random_int(5, 30)),
                        'permissions' => json_encode(['delete_posts' => true, 'ban_users' => fake()->boolean()]),
                        'is_active' => true,
                        'notes' => null,
                        'created_at' => now()->subDays(random_int(5, 30)),
                        'updated_at' => now()->subDays(random_int(0, 5)),
                    ]);
                }

                // Atualiza contador aproximado
                $community->update(['member_count' => count($members)]);
            }
            $this->command->info('✅ Membros e moderadores associados!');

            // 4-6) Criar posts, comentários e votos de forma incremental (baixa memória)
            $this->command->info('📝 Criando posts, comentários e votos...');
            $postCount = 0;
            $commentCount = 0;
            $postVoteCount = 0;
            $commentVoteCount = 0;

            foreach ($communities as $community) {
                $postsPerCommunity = app()->runningUnitTests() ? random_int(5, 10) : random_int(10, 25);

                for ($i = 0; $i < $postsPerCommunity; $i++) {
                    $author = $users->random();
                    $createdAt = now()->subDays(random_int(0, 60))->subMinutes(random_int(0, 1440));

                    $title = match ($community->name) {
                        'laravel' => fake('pt_BR')->randomElement([
                            'Dica de validação com FormRequest',
                            'Quando usar Jobs vs Events?',
                            'Eloquent: evitar N+1 de forma simples',
                        ]),
                        'vue' => fake('pt_BR')->randomElement([
                            'Composition API ou Options? Casos práticos',
                            'Melhores padrões para componentes de formulário',
                            'Inertia v2: como usar deferred props',
                        ]),
                        'javascript' => fake('pt_BR')->randomElement([
                            'Node, Deno ou Bun? Quando usar cada um',
                            'Promises, async/await e erros: boas práticas',
                            'Tree-shaking e code-splitting com Vite',
                        ]),
                        'python' => fake('pt_BR')->randomElement([
                            'Virtualenv vs Poetry: qual fluxo usar?',
                            'Pandas: dicas para acelerar DataFrames',
                            'FastAPI ou Django REST? Comparativo prático',
                        ]),
                        'futebol' => fake('pt_BR')->randomElement([
                            'Melhor escalação para a próxima rodada?',
                            'Opiniões sobre o clássico de ontem',
                            'Quem leva o Brasileirão este ano?',
                        ]),
                        'filmes' => fake('pt_BR')->randomElement([
                            'Filme subestimado que você recomenda',
                            'O que acharam do último lançamento da semana?',
                            'Top 5 filmes de ficção científica',
                        ]),
                        'animes' => fake('pt_BR')->randomElement([
                            'Arcos que mais te marcaram',
                            'Recomendações para iniciantes',
                            'Vale a pena ler o mangá antes do anime?',
                        ]),
                        'games' => fake('pt_BR')->randomElement([
                            'PC ou console para competitivo? Opiniões',
                            'Qual jogo te surpreendeu este ano?',
                            'Dicas de performance para jogos no PC',
                        ]),
                        'musica' => fake('pt_BR')->randomElement([
                            'Playlists para foco e produtividade',
                            'DAW favorita e por quê?',
                            'Mix e master: truques que você usa sempre',
                        ]),
                        default => fake('pt_BR')->sentence(6),
                    };

                    $text = match ($community->name) {
                        'laravel' => fake('pt_BR')->paragraphs(random_int(1, 3), true),
                        'vue' => fake('pt_BR')->paragraphs(random_int(1, 2), true),
                        'javascript' => fake('pt_BR')->paragraphs(random_int(1, 2), true),
                        'python' => fake('pt_BR')->paragraphs(random_int(1, 3), true),
                        default => fake('pt_BR')->optional()->paragraphs(random_int(1, 3), true),
                    };

                    $post = Post::factory()->create([
                        'community_id' => $community->id,
                        'user_id' => $author->id,
                        'title' => $title,
                        'text' => $text,
                        'image_url' => fake()->boolean(25) ? '/images/placeholders/post-image.svg' : null,
                        'is_pinned' => false,
                        'score' => 0,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt->copy()->addMinutes(random_int(0, 120)),
                    ]);
                    $postCount++;

                    // Comentários em threads
                    $threads = random_int(2, 5);
                    $createdCommentIds = [];
                    for ($t = 0; $t < $threads; $t++) {
                        $rootAuthor = $users->random();
                        $rootAt = $post->created_at->copy()->addHours(random_int(1, 72));
                        $root = Comment::factory()->create([
                            'post_id' => $post->id,
                            'user_id' => $rootAuthor->id,
                            'parent_id' => null,
                            'text' => fake('pt_BR')->sentences(random_int(1, 3), true),
                            'score' => 0,
                            'created_at' => $rootAt,
                            'updated_at' => $rootAt->copy()->addMinutes(random_int(0, 90)),
                        ]);
                        $commentCount++;
                        $createdCommentIds[] = $root->id;

                        // Respostas encadeadas
                        $replies = random_int(0, 3);
                        $parent = $root;
                        for ($r = 0; $r < $replies; $r++) {
                            $replyAuthor = $users->random();
                            $replyAt = $parent->created_at->copy()->addMinutes(random_int(10, 240));
                            $reply = Comment::factory()->create([
                                'post_id' => $post->id,
                                'user_id' => $replyAuthor->id,
                                'parent_id' => $parent->id,
                                'text' => fake('pt_BR')->sentences(random_int(1, 2), true),
                                'score' => 0,
                                'created_at' => $replyAt,
                                'updated_at' => $replyAt->copy()->addMinutes(random_int(0, 60)),
                            ]);
                            $commentCount++;
                            $createdCommentIds[] = $reply->id;
                            $parent = $reply;
                        }
                    }

                    // Votos no post
                    $voters = $users->shuffle()->take(random_int(5, 20));
                    $insertVotes = [];
                    $usedUserIds = [];
                    foreach ($voters as $voter) {
                        if (isset($usedUserIds[$voter->id])) {
                            continue;
                        }
                        $usedUserIds[$voter->id] = true;
                        $insertVotes[] = [
                            'post_id' => $post->id,
                            'user_id' => $voter->id,
                            'is_like' => fake()->boolean(80),
                            'created_at' => $post->created_at->copy()->addHours(random_int(1, 72)),
                'updated_at' => now(),
            ];
        }
                    if (!empty($insertVotes)) {
                        DB::table('post_votes')->insert($insertVotes);
                        $postVoteCount += count($insertVotes);
                    }

                    // Votos nos comentários criados deste post
                    if (!empty($createdCommentIds)) {
                        foreach ($createdCommentIds as $commentId) {
                            $commentVoters = $users->shuffle()->take(random_int(2, 8));
                            $insertCommentVotes = [];
                            $usedVoters = [];
                            foreach ($commentVoters as $voter) {
                                if (isset($usedVoters[$voter->id])) {
                                    continue;
                                }
                                $usedVoters[$voter->id] = true;
                                $insertCommentVotes[] = [
                                    'comment_id' => $commentId,
                                    'user_id' => $voter->id,
                                    'is_like' => fake()->boolean(75),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
                            if (!empty($insertCommentVotes)) {
                                DB::table('comment_votes')->insert($insertCommentVotes);
                                $commentVoteCount += count($insertCommentVotes);
                            }
                        }
                    }

                    // Atualizar score do post após votos
            $post->updateScore();
        }
            }
            $this->command->info('✅ Posts, comentários e votos criados!');

            // 8) Seguir comunidades para o primeiro usuário
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->communities()->syncWithoutDetaching($communities->take(5)->pluck('id'));
            $this->command->info('🙋 Primeiro usuário segue 5 comunidades por padrão.');
        }

        $this->command->info('🎉 Seed concluído com sucesso!');
        $this->command->info("📊 Estatísticas:");
        $this->command->info("- Usuários: " . $users->count());
        $this->command->info("- Comunidades: " . $communities->count());
            $this->command->info("- Posts: " . $postCount);
            $this->command->info("- Comentários: " . $commentCount);
            $this->command->info("- Votos em posts: " . $postVoteCount);
            $this->command->info("- Votos em comentários: " . $commentVoteCount);
        });
    }
}
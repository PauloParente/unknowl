<?php

namespace App\Services;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityService
{
    /**
     * Criar uma nova comunidade
     */
    public function createCommunity(User $owner, array $data): Community
    {
        return DB::transaction(function () use ($owner, $data) {
            // Processar uploads de arquivos
            $avatarPath = $this->handleAvatarUpload($data['avatar'] ?? null, $data['name']);
            $coverPath = $this->handleCoverUpload($data['cover'] ?? null);

            // Criar a comunidade
            $community = Community::create([
                'name' => $data['name'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'rules' => $data['rules'] ?? [],
                'avatar' => $avatarPath,
                'cover_url' => $coverPath,
                'owner_id' => $owner->id,
                'is_public' => $data['is_public'] ?? true,
                'requires_approval' => $data['requires_approval'] ?? false,
                'member_count' => 1, // O dono já é membro
                'last_activity_at' => now(),
            ]);

            // Adicionar o dono como membro
            $community->members()->attach($owner->id);

            // Adicionar o dono como moderador owner
            $community->moderators()->attach($owner->id, [
                'role' => CommunityRole::OWNER->value,
                'assigned_by' => $owner->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);

            return $community->fresh(['owner', 'members', 'moderators']);
        });
    }

    /**
     * Verificar se o nome da comunidade está disponível
     */
    public function isNameAvailable(string $name): bool
    {
        return !Community::where('name', $name)->exists();
    }

    /**
     * Sugerir nomes alternativos para uma comunidade
     */
    public function suggestAlternativeNames(string $baseName): array
    {
        $suggestions = [];
        $baseName = Str::slug($baseName);
        
        for ($i = 1; $i <= 5; $i++) {
            $suggestion = $baseName . $i;
            if ($this->isNameAvailable($suggestion)) {
                $suggestions[] = $suggestion;
            }
        }

        // Se ainda não temos sugestões, tentar com sufixos aleatórios
        if (empty($suggestions)) {
            $suffixes = ['br', 'pt', '2024', 'new', 'oficial'];
            foreach ($suffixes as $suffix) {
                $suggestion = $baseName . $suffix;
                if ($this->isNameAvailable($suggestion)) {
                    $suggestions[] = $suggestion;
                }
            }
        }

        return array_slice($suggestions, 0, 3);
    }

    /**
     * Processar upload do avatar
     */
    private function handleAvatarUpload(?UploadedFile $file, string $communityName): ?string
    {
        if (!$file) {
            // Retornar null para que o accessor do modelo gere um avatar padrão
            return null;
        }

        $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('communities/avatars', $filename, 'public');

        return $path;
    }

    /**
     * Processar upload da imagem de capa
     */
    private function handleCoverUpload(?UploadedFile $file, ?string $communityName = null): ?string
    {
        if (!$file) {
            // Se não há arquivo, retornar null para que o accessor do modelo gere uma capa padrão
            return null;
        }

        $filename = 'cover_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('communities/covers', $filename, 'public');

        return $path;
    }

    /**
     * Atualizar contador de membros
     */
    public function updateMemberCount(Community $community): void
    {
        $memberCount = $community->members()->count();
        $community->update(['member_count' => $memberCount]);
    }

    /**
     * Atualizar última atividade
     */
    public function updateLastActivity(Community $community): void
    {
        $community->update(['last_activity_at' => now()]);
    }

    /**
     * Adicionar moderador
     */
    public function addModerator(Community $community, User $user, CommunityRole $role, User $assignedBy, ?array $permissions = null, ?string $notes = null): bool
    {
        return $community->assignModerator($user, $role, $assignedBy, $permissions, $notes);
    }

    /**
     * Remover moderador
     */
    public function removeModerator(Community $community, User $user): bool
    {
        return $community->removeModerator($user);
    }

    /**
     * Atualizar capa da comunidade
     */
    public function updateCover(Community $community, UploadedFile $coverFile): Community
    {
        return DB::transaction(function () use ($community, $coverFile) {
            // Deletar capa anterior se existir
            if ($community->cover_url) {
                Storage::disk('public')->delete($community->cover_url);
            }

            // Processar upload da nova capa
            $coverPath = $this->handleCoverUpload($coverFile);

            // Atualizar a comunidade
            $community->update(['cover_url' => $coverPath]);

            return $community->fresh();
        });
    }

    /**
     * Deletar comunidade e seus assets
     */
    public function deleteCommunity(Community $community): void
    {
        DB::transaction(function () use ($community) {
            // Deletar arquivos de avatar e capa
            if ($community->avatar) {
                Storage::disk('public')->delete($community->avatar);
            }
            if ($community->cover_url) {
                Storage::disk('public')->delete($community->cover_url);
            }

            // Deletar a comunidade (cascade vai cuidar dos relacionamentos)
            $community->delete();
        });
    }
}

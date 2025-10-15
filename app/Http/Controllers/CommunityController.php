<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunityRequest;
use App\Http\Requests\UpdateCommunityCoverRequest;
use App\Http\Requests\CommunityAvatarUpdateRequest;
use App\Models\Community;
use App\Services\CommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends BaseController
{

    public function __construct(
        private CommunityService $communityService
    ) {
    }
    public function show(Request $request, string $name): Response
    {
        $cleanName = str_starts_with($name, 'r/') ? substr($name, 2) : $name;

        $community = Community::query()
            ->where('name', $cleanName)
            ->withCount(['posts', 'members'])
            ->firstOrFail();

        $user = $request->user();
        $posts = $community->posts()
            ->with(['community', 'author', 'votes' => function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            }])
            ->withCount('comments')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('pinned_at', 'desc')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($post) use ($user) {
                return $this->transformPost($post, $user?->id);
            })->values();

        // Simular usuários online (em um sistema real, isso viria de cache/Redis)
        $onlineCount = min($community->members_count, random_int(1, max(1, intval($community->members_count * 0.1))));

        // Obter informações do usuário atual na comunidade
        $userRole = $user ? $community->getUserRole($user) : null;
        $isMember = $user ? $community->hasMember($user) : false;
        $isOwner = $user ? $community->isOwnedBy($user) : false;
        $isModerator = $user ? $community->isModeratedBy($user) : false;
        $canEdit = $user ? $community->userHasRole($user, \App\Enums\CommunityRole::ADMIN) : false;

        return Inertia::render('communities/Show', [
            'name' => $cleanName,
            'community' => [
                'id' => $community->id,
                'name' => 'r/' . $community->name,
                'title' => $community->title,
                'description' => $community->description,
                'avatar' => $community->avatar,
                'cover_url' => $community->cover_url,
                'rules' => $community->rules,
                'posts_count' => $community->posts_count,
                'members_count' => $community->members_count,
                'online_count' => $onlineCount,
            ],
            'userRole' => $userRole?->value,
            'userRoleLabel' => $userRole?->getLabel(),
            'isMember' => $isMember,
            'isOwner' => $isOwner,
            'isModerator' => $isModerator,
            'canEdit' => $canEdit,
            'posts' => $posts,
        ]);
    }

    /**
     * Mostrar formulário de criação de comunidade
     */
    public function create(): Response
    {
        return Inertia::render('communities/Create');
    }

    /**
     * Armazenar uma nova comunidade
     */
    public function store(StoreCommunityRequest $request): RedirectResponse
    {
        $community = $this->communityService->createCommunity(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('communities.show', ['name' => $community->name])
            ->with('success', 'Comunidade criada com sucesso!');
    }

    /**
     * Verificar disponibilidade do nome da comunidade
     */
    public function checkNameAvailability(Request $request): JsonResponse
    {
        $name = $request->input('name');
        
        if (!$name) {
            return response()->json(['available' => false, 'message' => 'Nome é obrigatório']);
        }

        $available = $this->communityService->isNameAvailable($name);
        
        if (!$available) {
            $suggestions = $this->communityService->suggestAlternativeNames($name);
            
            return response()->json([
                'available' => false,
                'message' => 'Este nome já está em uso',
                'suggestions' => $suggestions,
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Nome disponível',
        ]);
    }

    /**
     * Atualizar configurações básicas da comunidade
     */
    public function updateSettings(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('updateSettings', $community);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $community->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Configurações atualizadas com sucesso!');
    }

    /**
     * Atualizar regras da comunidade
     */
    public function updateRules(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('updateRules', $community);

        $request->validate([
            'rules' => 'required|array|min:1',
            'rules.*' => 'required|string|max:500',
        ]);

        $community->update([
            'rules' => $request->rules,
        ]);

        return back()->with('success', 'Regras atualizadas com sucesso!');
    }

    /**
     * Atualizar posts fixados da comunidade
     */
    public function updatePinnedPosts(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('updatePinnedPosts', $community);

        $request->validate([
            'pinned_post_ids' => 'nullable|array|max:3',
            'pinned_post_ids.*' => 'required|integer|exists:posts,id',
        ]);

        // Verificar se todos os posts pertencem à comunidade
        $postIds = $request->input('pinned_post_ids', []);
        $validPostIds = $community->posts()->whereIn('id', $postIds)->pluck('id')->toArray();
        
        if (count($postIds) !== count($validPostIds)) {
            return back()->withErrors(['pinned_post_ids' => 'Alguns posts não pertencem a esta comunidade.']);
        }

        // Primeiro, desfixar todos os posts da comunidade
        $community->posts()->update([
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        // Depois, fixar os posts selecionados
        if (!empty($postIds)) {
            $community->posts()->whereIn('id', $postIds)->update([
                'is_pinned' => true,
                'pinned_at' => now(),
            ]);
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Posts fixados atualizados com sucesso!',
                'success' => true
            ]);
        }
        
        return back()->with('success', 'Posts fixados atualizados com sucesso!');
    }

    /**
     * Atualizar capa da comunidade
     */
    public function updateCover(UpdateCommunityCoverRequest $request, Community $community): RedirectResponse
    {
        $this->communityService->updateCover($community, $request->file('cover'));

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Capa atualizada com sucesso!',
                'success' => true,
                'cover_url' => $community->fresh()->cover_url
            ]);
        }

        return back()->with('success', 'Capa atualizada com sucesso!');
    }

    /**
     * Atualizar avatar da comunidade
     */
    public function updateAvatar(CommunityAvatarUpdateRequest $request, Community $community): RedirectResponse
    {
        // Deletar avatar anterior se existir
        if ($community->avatar && Storage::disk('public')->exists($community->avatar)) {
            Storage::disk('public')->delete($community->avatar);
        }

        // Salvar nova imagem
        $avatarPath = $request->file('avatar')->store('community-avatars', 'public');
        
        // Atualizar a comunidade
        $community->update(['avatar' => $avatarPath]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Avatar atualizado com sucesso!',
                'success' => true,
                'avatar' => $community->fresh()->avatar
            ]);
        }

        return back()->with('status', 'avatar-updated');
    }

    /**
     * Deletar avatar da comunidade
     */
    public function deleteAvatar(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('updateSettings', $community);
        
        // Deletar arquivo do storage se existir
        if ($community->avatar && Storage::disk('public')->exists($community->avatar)) {
            Storage::disk('public')->delete($community->avatar);
        }

        // Remover referência do avatar
        $community->update(['avatar' => null]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Avatar removido com sucesso!',
                'success' => true,
                'avatar' => null
            ]);
        }

        return back()->with('status', 'avatar-deleted');
    }
}



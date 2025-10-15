<?php

namespace App\Http\Controllers;

use App\Enums\CommunityRole;
use App\Enums\ModerationAction;
use App\Models\Community;
use App\Models\CommunityModerationLog;
use App\Models\CommunityBan;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommunityModerationController extends Controller
{
    /**
     * Dashboard de moderação da comunidade
     */
    public function dashboard(Community $community): Response
    {
        $this->authorize('moderateContent', $community);

        $stats = [
            'total_members' => $community->member_count,
            'active_moderators' => $community->moderators()->wherePivot('is_active', true)->count(),
            'pending_approvals' => $community->members()->wherePivot('approved_at', null)->count(),
            'recent_bans' => $community->bans()->active()->count(),
            'recent_activity' => $community->moderationLogs()
                ->with(['moderator', 'targetUser'])
                ->latest()
                ->limit(10)
                ->get(),
        ];

        return Inertia::render('Communities/Moderation/Dashboard', [
            'community' => $community->load(['owner', 'moderators' => function ($query) {
                $query->wherePivot('is_active', true);
            }]),
            'stats' => $stats,
        ]);
    }

    /**
     * Listar moderadores da comunidade
     */
    public function moderators(Community $community): Response
    {
        $this->authorize('manageModerators', $community);

        $moderators = $community->moderators()
            ->withPivot(['role', 'assigned_by', 'assigned_at', 'permissions', 'is_active', 'notes'])
            ->with(['assignedBy' => function ($query) {
                $query->select('id', 'name', 'avatar');
            }])
            ->get()
            ->map(function ($moderator) {
                return [
                    'id' => $moderator->id,
                    'name' => $moderator->name,
                    'avatar' => $moderator->avatar,
                    'role' => $moderator->pivot->role,
                    'role_label' => CommunityRole::from($moderator->pivot->role)->getLabel(),
                    'assigned_by' => $moderator->assignedBy,
                    'assigned_at' => $moderator->pivot->assigned_at,
                    'is_active' => $moderator->pivot->is_active,
                    'notes' => $moderator->pivot->notes,
                    'permissions' => $moderator->pivot->permissions,
                ];
            });

        return Inertia::render('Communities/Moderation/Moderators', [
            'community' => $community,
            'moderators' => $moderators,
            'canAddAdmin' => $community->canAddModerator(CommunityRole::ADMIN),
            'canAddModerator' => $community->canAddModerator(CommunityRole::MODERATOR),
        ]);
    }

    /**
     * Designar novo moderador
     */
    public function assignModerator(Request $request, Community $community): JsonResponse
    {
        $this->authorize('manageModerators', $community);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,moderator',
            'permissions' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $role = CommunityRole::from($request->role);

        // Verificar se pode designar este usuário
        if (!$community->userCanManage(Auth::user(), $user)) {
            return response()->json(['message' => 'Você não pode designar este usuário'], 403);
        }

        if (!$community->canAddModerator($role)) {
            return response()->json(['message' => 'Limite de moderadores atingido para este role'], 400);
        }

        DB::transaction(function () use ($community, $user, $role, $request) {
            // Designar moderador
            $success = $community->assignModerator(
                $user,
                $role,
                Auth::user(),
                $request->permissions,
                $request->notes
            );

            if (!$success) {
                throw new \Exception('Falha ao designar moderador');
            }

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::ASSIGN_MODERATOR,
                $user,
                $request->notes,
                ['role' => $role->value, 'permissions' => $request->permissions]
            );
        });

        return response()->json([
            'message' => 'Moderador designado com sucesso',
            'moderator' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'role' => $role->value,
                'role_label' => $role->getLabel(),
            ]
        ]);
    }

    /**
     * Remover moderador
     */
    public function removeModerator(Request $request, Community $community): JsonResponse
    {
        $this->authorize('manageModerators', $community);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);

        // Verificar se pode gerenciar este moderador
        if (!$community->userCanManage(Auth::user(), $user)) {
            return response()->json(['message' => 'Você não pode remover este moderador'], 403);
        }

        DB::transaction(function () use ($community, $user, $request) {
            // Remover moderador
            $community->removeModerator($user);

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::REMOVE_MODERATOR,
                $user,
                $request->reason
            );
        });

        return response()->json(['message' => 'Moderador removido com sucesso']);
    }

    /**
     * Alterar role de moderador
     */
    public function changeModeratorRole(Request $request, Community $community): JsonResponse
    {
        $this->authorize('manageModerators', $community);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'new_role' => 'required|in:admin,moderator',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $newRole = CommunityRole::from($request->new_role);

        // Verificar se pode gerenciar este moderador
        if (!$community->userCanManage(Auth::user(), $user)) {
            return response()->json(['message' => 'Você não pode alterar o role deste moderador'], 403);
        }

        if (!$community->canAddModerator($newRole)) {
            return response()->json(['message' => 'Limite de moderadores atingido para este role'], 400);
        }

        DB::transaction(function () use ($community, $user, $newRole, $request) {
            // Alterar role
            $success = $community->changeModeratorRole($user, $newRole, Auth::user());

            if (!$success) {
                throw new \Exception('Falha ao alterar role do moderador');
            }

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                $newRole->hasHigherAuthorityThan(CommunityRole::MODERATOR) 
                    ? ModerationAction::PROMOTE_MODERATOR 
                    : ModerationAction::DEMOTE_MODERATOR,
                $user,
                $request->reason,
                ['new_role' => $newRole->value]
            );
        });

        return response()->json(['message' => 'Role alterado com sucesso']);
    }

    /**
     * Banir usuário da comunidade
     */
    public function banUser(Request $request, Community $community): JsonResponse
    {
        $this->authorize('banUsers', $community);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:permanent,temporary',
            'reason' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = User::findOrFail($request->user_id);

        // Verificar se pode banir este usuário
        if (!$community->userCanManage(Auth::user(), $user)) {
            return response()->json(['message' => 'Você não pode banir este usuário'], 403);
        }

        // Verificar se já está banido
        if ($community->isUserBanned($user)) {
            return response()->json(['message' => 'Usuário já está banido desta comunidade'], 400);
        }

        DB::transaction(function () use ($community, $user, $request) {
            // Criar ban
            CommunityBan::createBan(
                $community,
                $user,
                Auth::user(),
                $request->type,
                $request->reason,
                $request->expires_at ? new \DateTime($request->expires_at) : null
            );

            // Log da ação
            CommunityModerationLog::createLog(
                $community,
                Auth::user(),
                ModerationAction::BAN_USER,
                $user,
                $request->reason,
                [
                    'type' => $request->type,
                    'expires_at' => $request->expires_at,
                ]
            );
        });

        return response()->json(['message' => 'Usuário banido com sucesso']);
    }

    /**
     * Desbanir usuário da comunidade
     */
    public function unbanUser(Request $request, Community $community): JsonResponse
    {
        $this->authorize('banUsers', $community);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($request->user_id);

        // Verificar se pode desbanir este usuário
        if (!$community->userCanManage(Auth::user(), $user)) {
            return response()->json(['message' => 'Você não pode desbanir este usuário'], 403);
        }

        $ban = $community->getActiveBan($user);
        if (!$ban) {
            return response()->json(['message' => 'Usuário não está banido desta comunidade'], 400);
        }

        DB::transaction(function () use ($ban, $request) {
            // Desbanir
            $ban->unban(Auth::user(), $request->reason);

            // Log da ação
            CommunityModerationLog::createLog(
                $ban->community,
                Auth::user(),
                ModerationAction::UNBAN_USER,
                $ban->user,
                $request->reason
            );
        });

        return response()->json(['message' => 'Usuário desbanido com sucesso']);
    }

    /**
     * Listar logs de moderação
     */
    public function logs(Community $community): Response
    {
        $this->authorize('viewModerationLogs', $community);

        $logs = $community->moderationLogs()
            ->with(['moderator:id,name,avatar', 'targetUser:id,name,avatar'])
            ->latest()
            ->paginate(50);

        return Inertia::render('Communities/Moderation/Logs', [
            'community' => $community,
            'logs' => $logs,
        ]);
    }

    /**
     * Buscar usuários para designar como moderadores
     */
    public function searchUsers(Request $request, Community $community): JsonResponse
    {
        $this->authorize('manageModerators', $community);

        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->where('id', '!=', Auth::id()) // Não pode designar a si mesmo
            ->whereNotIn('id', function ($q) use ($community) {
                $q->select('user_id')
                  ->from('community_moderators')
                  ->where('community_id', $community->id)
                  ->where('is_active', true);
            })
            ->select('id', 'name', 'avatar')
            ->limit(10)
            ->get();

        return response()->json(['users' => $users]);
    }
}
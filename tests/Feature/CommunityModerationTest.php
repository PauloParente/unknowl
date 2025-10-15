<?php

use App\Enums\CommunityRole;
use App\Enums\ModerationAction;
use App\Models\Community;
use App\Models\CommunityBan;
use App\Models\CommunityModerationLog;
use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->moderator = User::factory()->create();
    $this->member = User::factory()->create();
    $this->regularUser = User::factory()->create();

    $this->community = Community::factory()->create([
        'owner_id' => $this->owner->id,
    ]);

    // Adicionar membros à comunidade
    $this->community->members()->attach([
        $this->owner->id,
        $this->admin->id,
        $this->moderator->id,
        $this->member->id,
    ]);

    // Designar roles
    $this->community->moderators()->attach($this->admin->id, [
        'role' => CommunityRole::ADMIN->value,
        'assigned_by' => $this->owner->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    $this->community->moderators()->attach($this->moderator->id, [
        'role' => CommunityRole::MODERATOR->value,
        'assigned_by' => $this->admin->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
});

describe('Community Roles and Permissions', function () {
    it('can identify user roles correctly', function () {
        expect($this->community->getUserRole($this->owner))->toBe(CommunityRole::OWNER);
        expect($this->community->getUserRole($this->admin))->toBe(CommunityRole::ADMIN);
        expect($this->community->getUserRole($this->moderator))->toBe(CommunityRole::MODERATOR);
        expect($this->community->getUserRole($this->member))->toBe(CommunityRole::MEMBER);
        expect($this->community->getUserRole($this->regularUser))->toBeNull();
    });

    it('can check if user has required role', function () {
        expect($this->community->userHasRole($this->owner, CommunityRole::OWNER))->toBeTrue();
        expect($this->community->userHasRole($this->admin, CommunityRole::ADMIN))->toBeTrue();
        expect($this->community->userHasRole($this->moderator, CommunityRole::MODERATOR))->toBeTrue();
        
        // Hierarquia
        expect($this->community->userHasRole($this->owner, CommunityRole::ADMIN))->toBeTrue();
        expect($this->community->userHasRole($this->admin, CommunityRole::MODERATOR))->toBeTrue();
        expect($this->community->userHasRole($this->moderator, CommunityRole::ADMIN))->toBeFalse();
    });

    it('can check if user can manage another user', function () {
        // Owner pode gerenciar todos
        expect($this->community->userCanManage($this->owner, $this->admin))->toBeTrue();
        expect($this->community->userCanManage($this->owner, $this->moderator))->toBeTrue();
        expect($this->community->userCanManage($this->owner, $this->member))->toBeTrue();

        // Admin pode gerenciar moderator e member
        expect($this->community->userCanManage($this->admin, $this->moderator))->toBeTrue();
        expect($this->community->userCanManage($this->admin, $this->member))->toBeTrue();
        expect($this->community->userCanManage($this->admin, $this->owner))->toBeFalse();

        // Moderator não pode gerenciar ninguém
        expect($this->community->userCanManage($this->moderator, $this->member))->toBeFalse();
    });
});
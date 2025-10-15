<?php

use App\Models\Community;
use App\Models\CommunityBan;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->moderator = User::factory()->create();
    $this->member = User::factory()->create();

    $this->community = Community::factory()->create([
        'owner_id' => $this->owner->id,
    ]);

    $this->community->members()->attach([
        $this->owner->id,
        $this->moderator->id,
        $this->member->id,
    ]);

    $this->community->moderators()->attach($this->moderator->id, [
        'role' => 'moderator',
        'assigned_by' => $this->owner->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
});

describe('Community Bans', function () {
    it('can ban user from community', function () {
        $ban = CommunityBan::createBan(
            $this->community,
            $this->member,
            $this->moderator,
            'permanent',
            'Spam e comportamento inadequado'
        );

        expect($ban)->toBeInstanceOf(CommunityBan::class);
        expect($this->community->isUserBanned($this->member))->toBeTrue();
        expect(CommunityBan::isUserBanned($this->member, $this->community))->toBeTrue();
    });

    it('can create temporary ban', function () {
        $expiresAt = now()->addDays(7);
        
        $ban = CommunityBan::createBan(
            $this->community,
            $this->member,
            $this->moderator,
            'temporary',
            'Ban temporário por 7 dias',
            $expiresAt
        );

        expect($ban->isTemporary())->toBeTrue();
        expect($ban->isActive())->toBeTrue();
        expect($ban->expires_at->format('Y-m-d'))->toBe($expiresAt->format('Y-m-d'));
    });

    it('can unban user', function () {
        $ban = CommunityBan::createBan(
            $this->community,
            $this->member,
            $this->moderator,
            'permanent',
            'Test ban'
        );

        $result = $ban->unban($this->owner, 'Ban removido após apelação');

        expect($result)->toBeTrue();
        expect($ban->fresh()->is_active)->toBeFalse();
        expect($this->community->isUserBanned($this->member))->toBeFalse();
    });

    it('can check if user is banned', function () {
        expect($this->community->isUserBanned($this->member))->toBeFalse();

        CommunityBan::createBan($this->community, $this->member, $this->moderator);

        expect($this->community->isUserBanned($this->member))->toBeTrue();
    });

    it('can get active ban for user', function () {
        $ban = CommunityBan::createBan($this->community, $this->member, $this->moderator);

        $activeBan = $this->community->getActiveBan($this->member);
        
        expect($activeBan)->toBeInstanceOf(CommunityBan::class);
        expect($activeBan->id)->toBe($ban->id);
    });

    it('returns null when user is not banned', function () {
        $activeBan = $this->community->getActiveBan($this->member);
        
        expect($activeBan)->toBeNull();
    });
});
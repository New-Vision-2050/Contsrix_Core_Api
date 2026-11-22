<?php

declare(strict_types=1);

namespace Modules\Forum\Tests\Unit;

use Mockery;
use Modules\Forum\Enums\ForumMemberRole;
use Modules\Forum\Enums\ForumSendPermission;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\Forum\Services\ForumAccessService;
use Modules\User\Models\User;
use Tests\TestCase;

class ForumAccessServiceTest extends TestCase
{
    private ForumAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ForumAccessService();
    }

    private function user(bool $admin = false): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->is_owner = false;
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        $user->shouldReceive('can')->andReturn($admin);

        return $user;
    }

    private function group(ForumSendPermission $permission, array $flags = []): ForumGroup
    {
        return new ForumGroup($flags + [
            'send_permission' => $permission,
            'allow_images' => true,
            'allow_voice' => true,
            'allow_files' => true,
        ]);
    }

    private function member(ForumMemberRole $role = ForumMemberRole::MEMBER, bool $canSend = false): ForumGroupMember
    {
        return new ForumGroupMember(['role' => $role, 'can_send' => $canSend]);
    }

    public function test_non_member_cannot_read_or_send(): void
    {
        $group = $this->group(ForumSendPermission::ALL_MEMBERS);
        $user = $this->user();

        $this->assertFalse($this->service->canView($group, $user, null));
        $this->assertFalse($this->service->canSend($group, $user, null));
        $this->assertSame([], $this->service->allowedTypes($group, $user, null));
    }

    public function test_forum_admin_can_read_and_send_without_membership(): void
    {
        $group = $this->group(ForumSendPermission::ADMINS_ONLY);
        $admin = $this->user(true);

        $this->assertTrue($this->service->canView($group, $admin, null));
        $this->assertTrue($this->service->canSend($group, $admin, null));
    }

    public function test_admins_only_group_allows_only_group_admins(): void
    {
        $group = $this->group(ForumSendPermission::ADMINS_ONLY);
        $user = $this->user();

        $this->assertFalse($this->service->canSend($group, $user, $this->member()));
        $this->assertTrue($this->service->canSend($group, $user, $this->member(ForumMemberRole::ADMIN)));
    }

    public function test_all_members_group_allows_any_member(): void
    {
        $group = $this->group(ForumSendPermission::ALL_MEMBERS);

        $this->assertTrue($this->service->canSend($group, $this->user(), $this->member()));
    }

    public function test_specific_members_group_requires_can_send_flag(): void
    {
        $group = $this->group(ForumSendPermission::SPECIFIC_MEMBERS);
        $user = $this->user();

        $this->assertFalse($this->service->canSend($group, $user, $this->member()));
        $this->assertTrue($this->service->canSend($group, $user, $this->member(canSend: true)));
    }

    public function test_allowed_types_follow_group_toggles(): void
    {
        $group = $this->group(ForumSendPermission::ALL_MEMBERS, ['allow_images' => false, 'allow_voice' => false]);

        $this->assertSame(
            ['text', 'file'],
            $this->service->allowedTypes($group, $this->user(), $this->member())
        );
    }

    public function test_access_payload_for_member(): void
    {
        $group = $this->group(ForumSendPermission::ADMINS_ONLY);
        $access = $this->service->accessFor($group, $this->user(), $this->member());

        $this->assertTrue($access['is_member']);
        $this->assertTrue($access['can_read']);
        $this->assertFalse($access['can_send']);
        $this->assertFalse($access['can_manage']);
        $this->assertSame('member', $access['my_role']);
    }
}

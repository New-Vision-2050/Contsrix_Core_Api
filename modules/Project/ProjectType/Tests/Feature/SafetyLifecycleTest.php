<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Events\SafetyTaskAssigned as SafetyTaskAssignedEvent;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Notifications\SafetyTaskAssigned as SafetyTaskAssignedNotification;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Tests\TestCase;

/**
 * End-to-end Safety & Violations lifecycle tests.
 *
 * Uses the existing project UUID (projects.id is not auto-generated).
 */
final class SafetyLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private const EXISTING_PROJECT_ID = '9a79b5b5-7e91-11f1-817a-bce92f8cda2e';

    private Company $company;

    private ProjectManagement $project;

    private User $actor;

    private User $assignee;

    private User $assigneeTwo;

    private User $outsider;

    private ManagementHierarchy $branch;

    private ManagementHierarchy $management;

    private ManagementHierarchy $department;

    private ProjectNotification $projectNotification;

    private Violation $violationOne;

    private Violation $violationTwo;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->safetyTablesReady()) {
            $this->markTestSkipped('Safety tables are missing. Run migrations before executing this suite.');
        }

        $this->project = ProjectManagement::withoutGlobalScopes()->find(self::EXISTING_PROJECT_ID);

        if (! $this->project) {
            $this->markTestSkipped('Existing project '.self::EXISTING_PROJECT_ID.' was not found.');
        }

        $this->company = Company::withoutGlobalScopes()->find($this->project->company_id);

        if (! $this->company) {
            $this->markTestSkipped('Company for existing project was not found.');
        }

        tenancy()->initialize($this->company);

        // Re-load project in tenant context if connection switched
        $this->project = ProjectManagement::withoutGlobalScopes()->find(self::EXISTING_PROJECT_ID)
            ?? $this->project;

        $this->ensureHierarchy();

        $this->actor = $this->createProjectUser('Safety Actor');
        $this->assignee = $this->createProjectUser('Safety Assignee One');
        $this->assigneeTwo = $this->createProjectUser('Safety Assignee Two');
        $this->outsider = $this->createProjectUser('Safety Outsider');

        $this->assignToProject($this->assignee);
        $this->assignToProject($this->assigneeTwo);

        $this->projectNotification = $this->createProjectNotification();
        [$this->violationOne, $this->violationTwo] = $this->createViolations();
    }

    public function test_create_safety_task_creates_pending_records_notifications_and_broadcasts(): void
    {
        Event::fake([SafetyTaskAssignedEvent::class]);

        $beforeCount = SafetyRecord::query()
            ->where('project_id', $this->project->id)
            ->where('status', 'pending')
            ->whereIn('assigned_user_id', [$this->assignee->id, $this->assigneeTwo->id])
            ->count();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety', [
                'morphable_type' => 'project_notification',
                'morphable_id' => $this->projectNotification->id,
                'assigned_user_ids' => [(string) $this->assignee->id, (string) $this->assigneeTwo->id],
                'order_type' => 'صيانة',
                'date' => now()->toDateString(),
                'time' => '09:30',
                'required_score' => 100,
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'not_applicable',
                    ],
                ],
            ]);

        $response->assertOk();
        $payload = $response->json('payload');
        $this->assertIsArray($payload);
        $this->assertCount(2, $payload);

        $this->assertSame($beforeCount + 2, SafetyRecord::query()
            ->where('project_id', $this->project->id)
            ->where('status', 'pending')
            ->whereIn('assigned_user_id', [(string) $this->assignee->id, (string) $this->assigneeTwo->id])
            ->count());

        $this->assertDatabaseHas('safety_records', [
            'project_id' => $this->project->id,
            'assigned_user_id' => (string) $this->assignee->id,
            'status' => 'pending',
            'morphable_type' => 'project_notification',
            'morphable_id' => $this->projectNotification->id,
        ]);

        $this->assertDatabaseHas('safety_records', [
            'project_id' => $this->project->id,
            'assigned_user_id' => (string) $this->assigneeTwo->id,
            'status' => 'pending',
        ]);

        if (Schema::hasTable('notifications')) {
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => (string) $this->assignee->id,
                'type' => SafetyTaskAssignedNotification::class,
            ]);
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => (string) $this->assigneeTwo->id,
                'type' => SafetyTaskAssignedNotification::class,
            ]);
        }

        Event::assertDispatched(SafetyTaskAssignedEvent::class, 2);
        Event::assertDispatched(SafetyTaskAssignedEvent::class, function (SafetyTaskAssignedEvent $event) {
            return in_array((string) $event->safetyRecord->assigned_user_id, [
                (string) $this->assignee->id,
                (string) $this->assigneeTwo->id,
            ], true)
                && $event->safetyRecord->status === 'pending';
        });
    }

    public function test_assigned_user_sees_pending_task_in_inbox(): void
    {
        $record = $this->createSafetyRecord($this->assignee);

        $response = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/safety/inbox');

        $response->assertOk();

        $ids = collect($response->json('payload'))->pluck('id');
        $this->assertTrue($ids->contains($record->id));
        $this->assertSame('pending', collect($response->json('payload'))
            ->firstWhere('id', $record->id)['status']);
    }

    public function test_completed_task_does_not_appear_when_filtering_pending_inbox(): void
    {
        $record = $this->createSafetyRecord($this->assignee, ['status' => 'completed']);

        $response = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/safety/inbox?status=pending');

        $response->assertOk();

        $ids = collect($response->json('payload'))->pluck('id');
        $this->assertFalse($ids->contains($record->id));
    }

    public function test_assigned_user_can_evaluate_violations_and_complete_task(): void
    {
        $record = $this->createSafetyRecord($this->assignee);

        $response = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'violation_found',
                        'action' => 'stop_work',
                    ],
                    [
                        'violation_id' => $this->violationTwo->id,
                        'weight' => 2,
                        'status' => 'no_violation',
                    ],
                ],
            ]);

        $response->assertOk();
        $this->assertSame('completed', $response->json('payload.status'));

        // violation_found(-7) + no_violation(+2) = earned -5; abs total 9 → -55.56%
        $this->assertEquals(-5.0, (float) $response->json('payload.earned_score'));
        $this->assertEquals(9.0, (float) $response->json('payload.required_score'));
        $this->assertEquals(-55.56, (float) $response->json('payload.percentage'));

        $violationOnePayload = collect($response->json('payload.all_violations'))
            ->firstWhere('id', $this->violationOne->id);
        $violationTwoPayload = collect($response->json('payload.all_violations'))
            ->firstWhere('id', $this->violationTwo->id);

        $this->assertSame('stop_work', $violationOnePayload['action']);
        $this->assertNull($violationTwoPayload['action']);

        $this->assertDatabaseHas('safety_records', [
            'id' => $record->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('safety_record_violation', [
            'safety_record_id' => $record->id,
            'violation_id' => $this->violationOne->id,
            'weight' => -7,
            'status' => 'violation_found',
            'action' => 'stop_work',
        ]);

        $this->assertDatabaseHas('safety_record_violation', [
            'safety_record_id' => $record->id,
            'violation_id' => $this->violationTwo->id,
            'weight' => 2,
            'status' => 'no_violation',
            'action' => null,
        ]);
    }

    public function test_evaluating_violation_found_without_action_succeeds(): void
    {
        $record = $this->createSafetyRecord($this->assignee);

        $response = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'violation_found',
                    ],
                ],
            ]);

        $response->assertOk();

        $violationPayload = collect($response->json('payload.all_violations'))
            ->firstWhere('id', $this->violationOne->id);
        $this->assertNull($violationPayload['action']);

        $this->assertDatabaseHas('safety_record_violation', [
            'safety_record_id' => $record->id,
            'violation_id' => $this->violationOne->id,
            'status' => 'violation_found',
            'action' => null,
        ]);
    }

    public function test_no_violation_and_not_applicable_do_not_require_action(): void
    {
        $record = $this->createSafetyRecord($this->assignee);
        $violationThree = Violation::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'SAFE-C-'.Str::upper(Str::random(4)),
            'description' => 'Test violation C',
            'category' => 'C',
            'default_weight' => 0.5,
        ]);

        $response = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'no_violation',
                    ],
                    [
                        'violation_id' => $violationThree->id,
                        'status' => 'not_applicable',
                    ],
                ],
            ]);

        $response->assertOk();

        $payload = collect($response->json('payload.all_violations'));
        $this->assertNull($payload->firstWhere('id', $this->violationOne->id)['action']);
        $this->assertNull($payload->firstWhere('id', $violationThree->id)['action']);

        $this->assertDatabaseHas('safety_record_violation', [
            'safety_record_id' => $record->id,
            'violation_id' => $this->violationOne->id,
            'status' => 'no_violation',
            'action' => null,
        ]);
        $this->assertDatabaseHas('safety_record_violation', [
            'safety_record_id' => $record->id,
            'violation_id' => $violationThree->id,
            'status' => 'not_applicable',
            'action' => null,
        ]);
    }

    public function test_evaluating_violation_found_with_exclude_equipment_action_succeeds(): void
    {
        $record = $this->createSafetyRecord($this->assignee);

        $response = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'violation_found',
                        'action' => 'exclude_equipment',
                    ],
                ],
            ]);

        $response->assertOk();

        $violationPayload = collect($response->json('payload.all_violations'))
            ->firstWhere('id', $this->violationOne->id);
        $this->assertSame('exclude_equipment', $violationPayload['action']);

        $this->assertDatabaseHas('safety_record_violation', [
            'safety_record_id' => $record->id,
            'violation_id' => $this->violationOne->id,
            'status' => 'violation_found',
            'action' => 'exclude_equipment',
        ]);
    }

    public function test_completed_record_cannot_be_updated_or_deleted(): void
    {
        $record = $this->createSafetyRecord($this->assignee, ['status' => 'completed']);

        $update = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id, [
                'order_type' => 'محاولة تعديل',
            ]);

        $update->assertStatus(422);
        $this->assertStringContainsString('مكتملة', (string) $update->json('message.description'));

        $delete = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id);

        $delete->assertStatus(422);
        $this->assertStringContainsString('مكتملة', (string) $delete->json('message.description'));

        $this->assertDatabaseHas('safety_records', [
            'id' => $record->id,
            'status' => 'completed',
        ]);
    }

    public function test_non_assigned_user_cannot_evaluate_task(): void
    {
        $record = $this->createSafetyRecord($this->assignee);

        $response = $this->actingAs($this->outsider, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'no_violation',
                    ],
                ],
            ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('غير مصرح', (string) $response->json('message.description'));

        $this->assertDatabaseHas('safety_records', [
            'id' => $record->id,
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_evaluation_is_rejected_after_completion(): void
    {
        $record = $this->createSafetyRecord($this->assignee);

        $first = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationOne->id,
                        'weight' => 7,
                        'status' => 'no_violation',
                    ],
                ],
            ]);

        $first->assertOk();
        $this->assertSame('completed', $first->json('payload.status'));

        $second = $this->actingAs($this->assignee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violations', [
                'violations' => [
                    [
                        'violation_id' => $this->violationTwo->id,
                        'weight' => 2,
                        'status' => 'violation_found',
                        'action' => 'stop_work',
                    ],
                ],
            ]);

        $second->assertStatus(422);
        $this->assertStringContainsString('مكتملة', (string) $second->json('message.description'));
    }

    public function test_create_with_invalid_morphable_type_fails_validation(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety', [
                'morphable_type' => 'invalid_type',
                'morphable_id' => $this->projectNotification->id,
                'assigned_user_ids' => [$this->assignee->id],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['morphable_type']);
    }

    public function test_create_with_non_existent_user_fails_validation(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety', [
                'morphable_type' => 'project_notification',
                'morphable_id' => $this->projectNotification->id,
                'assigned_user_ids' => [(string) Str::uuid()],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assigned_user_ids.0']);
    }

    public function test_create_without_assigned_users_fails_validation(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety', [
                'morphable_type' => 'project_notification',
                'morphable_id' => $this->projectNotification->id,
                'assigned_user_ids' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assigned_user_ids']);
    }

    public function test_create_with_user_not_in_project_fails(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.$this->project->id.'/safety', [
                'morphable_type' => 'project_notification',
                'morphable_id' => $this->projectNotification->id,
                'assigned_user_ids' => [$this->outsider->id],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('ليس موظفاً', (string) $response->json('message.description'));
    }

    public function test_list_violations_catalog(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/violations');

        $response->assertOk();

        $payload = collect($response->json('payload'));
        $ids = $payload->pluck('id');
        $this->assertTrue($ids->contains($this->violationOne->id));
        $this->assertTrue($ids->contains($this->violationTwo->id));

        $one = $payload->firstWhere('id', $this->violationOne->id);
        $two = $payload->firstWhere('id', $this->violationTwo->id);

        $this->assertSame(
            ['إيقاف العمل', 'استبعاد المعدة أو الموظف'],
            $one['actions'] ?? null
        );
        $this->assertSame([], $two['actions'] ?? null);
    }

    private function safetyTablesReady(): bool
    {
        try {
            return Schema::hasTable('safety_records')
                && Schema::hasTable('violations')
                && Schema::hasTable('safety_record_violation')
                && Schema::hasTable('project_notifications')
                && Schema::hasTable('project_employees')
                && Schema::hasTable('projects')
                && Schema::hasTable('companies')
                && Schema::hasTable('users');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureHierarchy(): void
    {
        $this->branch = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'branch',
                'name' => 'Safety Test Branch',
            ],
            ['id' => (string) Str::uuid()]
        ));

        $this->management = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'management',
                'name' => 'Safety Test Management',
            ],
            ['id' => (string) Str::uuid()]
        ));

        $this->department = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'department',
                'name' => 'Safety Test Department',
                'parent_id' => $this->management->id,
            ],
            ['id' => (string) Str::uuid()]
        ));
    }

    private function createProjectUser(string $name): User
    {
        $globalId = (string) Str::uuid();

        $user = User::factory()->create([
            'name' => $name,
            'company_id' => $this->company->id,
            'global_company_user_id' => $globalId,
        ]);

        UserProfessionalData::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $globalId,
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'management_id' => (string) $this->management->id,
            'department_id' => $this->department->id,
        ]);

        return $user;
    }

    private function assignToProject(User $user): ProjectEmployee
    {
        return ProjectEmployee::query()->create([
            'id' => (string) Str::uuid(),
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $this->actor->id,
        ]);
    }

    private function createProjectNotification(): ProjectNotification
    {
        return ProjectNotification::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'notification_number' => 'NTF-SAFETY-'.Str::upper(Str::random(6)),
            'status' => 'pending',
            'created_by_user_id' => $this->actor->id,
            'work_description' => 'Safety morphable parent',
        ]);
    }

    /**
     * @return array{0: Violation, 1: Violation}
     */
    private function createViolations(): array
    {
        $one = Violation::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'SAFE-A-'.Str::upper(Str::random(4)),
            'description' => 'Test violation A',
            'category' => 'A',
            'default_weight' => 7,
            'work_cancellation' => false,
            'work_stop' => true,
            'equipment_exclusion' => true,
        ]);

        $two = Violation::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'SAFE-B-'.Str::upper(Str::random(4)),
            'description' => 'Test violation B',
            'category' => 'B',
            'default_weight' => 2,
            'work_cancellation' => false,
            'work_stop' => false,
            'equipment_exclusion' => false,
        ]);

        return [$one, $two];
    }

    private function createSafetyRecord(User $assignee, array $overrides = []): SafetyRecord
    {
        $record = SafetyRecord::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'morphable_type' => 'project_notification',
            'morphable_id' => $this->projectNotification->id,
            'order_type' => 'صيانة',
            'date' => now()->toDateString(),
            'time' => '09:30',
            'assigned_user_id' => $assignee->id,
            'status' => 'pending',
        ], $overrides));

        if (isset($overrides['status']) && $record->status !== $overrides['status']) {
            $record->forceFill(['status' => $overrides['status']])->saveQuietly();
            $record->refresh();
        }

        return $record;
    }
}

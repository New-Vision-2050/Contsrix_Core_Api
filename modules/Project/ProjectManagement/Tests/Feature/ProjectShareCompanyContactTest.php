<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\Shared\ResourceShare\Presenters\ResourceSharePresenter;

class ProjectShareCompanyContactTest extends BaseAttendanceReportTestCase
{
    public function test_shared_companies_returns_receiver_company_contact_fields(): void
    {
        $receiverCompany = $this->createCompany([
            'email' => 'receiver@example.test',
            'phone' => '01012345678',
            'serial_no' => 'REC-001',
        ]);
        $project = $this->createProject($this->company);

        $this->createAcceptedShare($project, $this->company, $receiverCompany);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/sharing/projects/'.$project->id.'/shared-companies')
            ->assertOk()
            ->assertJsonPath('payload.0.id', (string) $receiverCompany->id)
            ->assertJsonPath('payload.0.email', 'receiver@example.test')
            ->assertJsonPath('payload.0.phone', '01012345678')
            ->assertJsonPath('payload.0.serial_no', 'REC-001')
            ->assertJsonPath('payload.0.serial_number', 'REC-001');
    }

    public function test_shared_companies_returns_owner_company_contact_when_project_is_shared_with_current_company(): void
    {
        $ownerCompany = $this->createCompany([
            'email' => 'owner@example.test',
            'phone' => '01112345678',
            'serial_no' => 'OWN-001',
        ]);
        $project = $this->createProject($ownerCompany);

        $this->createAcceptedShare($project, $ownerCompany, $this->company);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/sharing/projects/'.$project->id.'/shared-companies')
            ->assertOk()
            ->assertJsonPath('payload.0.id', (string) $ownerCompany->id)
            ->assertJsonPath('payload.0.role', 'owner')
            ->assertJsonPath('payload.0.email', 'owner@example.test')
            ->assertJsonPath('payload.0.phone', '01112345678')
            ->assertJsonPath('payload.0.serial_no', 'OWN-001');
    }

    public function test_shared_companies_falls_back_to_main_branch_contact_fields(): void
    {
        $receiverCompany = $this->createCompany([
            'email' => null,
            'phone' => null,
            'serial_no' => 'REC-FALLBACK',
        ]);
        $this->createMainBranch($receiverCompany, [
            'email' => 'branch@example.test',
            'phone' => '01212345678',
            'phone_code' => '20',
        ]);
        $project = $this->createProject($this->company);

        $this->createAcceptedShare($project, $this->company, $receiverCompany);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/sharing/projects/'.$project->id.'/shared-companies')
            ->assertOk()
            ->assertJsonPath('payload.0.email', 'branch@example.test')
            ->assertJsonPath('payload.0.phone', '01212345678')
            ->assertJsonPath('payload.0.phone_code', '20');
    }

    public function test_resource_share_presenter_returns_shared_company_contact_fields(): void
    {
        $receiverCompany = $this->createCompany([
            'email' => 'presenter@example.test',
            'phone' => '01512345678',
            'serial_no' => 'PRS-001',
        ]);
        $project = $this->createProject($this->company);
        $share = $this->createAcceptedShare($project, $this->company, $receiverCompany)
            ->load(['ownerCompany.mainBranch', 'sharedWithCompany.mainBranch']);

        $payload = (new ResourceSharePresenter($share))->getData();

        $this->assertSame('presenter@example.test', $payload['shared_with_company']['email']);
        $this->assertSame('01512345678', $payload['shared_with_company']['phone']);
        $this->assertSame('PRS-001', $payload['shared_with_company']['serial_no']);
        $this->assertSame('PRS-001', $payload['shared_with_company']['serial_number']);
    }

    public function test_project_shares_response_does_not_include_flat_project_procedure_metadata(): void
    {
        $receiverCompany = $this->createCompany([
            'serial_no' => 'REC-META',
        ]);
        $project = $this->createProject($this->company);

        $this->createAcceptedShare($project, $this->company, $receiverCompany);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/sharing/projects/'.$project->id.'/shares?project_id='.$project->id)
            ->assertOk();

        $payload = $response->json('payload.0');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('shareable', $payload);
        $this->assertArrayNotHasKey('classification_name', $payload);
        $this->assertArrayNotHasKey('linked_folder_name', $payload);
        $this->assertArrayNotHasKey('classification_code', $payload);
        $this->assertArrayNotHasKey('document_nature', $payload);
        $this->assertArrayNotHasKey('job_attribute', $payload);
    }

    private function createProject(Company $company): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId($company);

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Shared Contact Project',
            'company_id' => $company->id,
            'status' => 1,
            'serial_number' => 'SHARE-'.Str::upper(Str::random(6)),
        ]));
    }

    private function projectTypeId(Company $company): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Shared Contact Test Type',
                'company_id' => $company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Shared Company'],
            'user_name' => 'shared_'.Str::lower(Str::random(6)),
            'email' => 'company@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'SHR-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createMainBranch(Company $company, array $overrides = []): ManagementHierarchy
    {
        return ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->create(array_merge([
            'name' => 'Main Branch',
            'type' => 'branch',
            'company_id' => $company->id,
            'parent_id' => null,
            'is_main' => 1,
        ], $overrides)));
    }

    private function createAcceptedShare(
        ProjectManagement $project,
        Company $ownerCompany,
        Company $receiverCompany
    ): ResourceShare {
        return ResourceShare::query()->create([
            'id' => (string) Str::uuid(),
            'shareable_type' => ProjectManagement::class,
            'shareable_id' => $project->id,
            'owner_company_id' => $ownerCompany->id,
            'shared_with_company_id' => $receiverCompany->id,
            'status' => 'accepted',
            'schema_ids' => [1, 2],
            'shared_by_user_id' => $this->actor->id,
            'responded_by_user_id' => $this->actor->id,
            'responded_at' => now(),
        ]);
    }
}

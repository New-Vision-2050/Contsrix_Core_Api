<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Models\User;

class ClientCompanyDraftWizardTest extends BaseCompanyTestCase
{
    private function stepOnePayload(array $overrides = []): array
    {
        $data = $this->companyService->generateTestData();
        $data['company_field_id'] = [$data['company_field_id']];
        $data['general_manager_id'] = $this->user->id->toString();

        return array_merge($data, $overrides);
    }

    private function createDraftClientCompany(): Company
    {
        $response = $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson(route('companies.store') . '?is_client=1', $this->stepOnePayload());

        $response->assertStatus(200);

        return Company::query()->findOrFail($response->json('payload.id'));
    }

    private function makeCompanyOwnerReady(Company $company): void
    {
        Role::query()->withoutTenancy()->firstOrCreate(
            ['name' => 'super-admin', 'company_id' => $company->id],
            ['guard_name' => 'web', 'status' => 1]
        );

        $branch = ManagementHierarchy::query()->withoutTenancy()->firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => null, 'type' => 'branch'],
            ['name' => 'Main Branch', 'is_first_branch' => 1, 'is_main' => 1]
        );

        ManagementHierarchy::query()->withoutTenancy()->firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => $branch->id, 'type' => 'management', 'is_main' => 1],
            ['name' => 'Main Management']
        );
    }

    private function makeCurrentTenantReady(): void
    {
        $this->makeCompanyOwnerReady($this->company);
        $this->user->update([
            'company_id' => $this->company->id,
            'is_owner' => 1,
        ]);
    }

    public function test_client_company_post_creates_draft(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson(route('companies.store') . '?is_client=1', $this->stepOnePayload());

        $response->assertStatus(200)
            ->assertJsonPath('payload.is_draft', true);

        $this->assertDatabaseHas('companies', [
            'id' => $response->json('payload.id'),
            'is_client' => 1,
            'is_draft' => 1,
        ]);
    }

    public function test_draft_client_companies_are_excluded_from_lists(): void
    {
        $draft = $this->createDraftClientCompany();

        $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson(route('companies.index'))
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $draft->id]);

        $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson(route('companies.clients'))
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $draft->id]);
    }

    public function test_put_updates_draft_step_one_without_creating_duplicate_company(): void
    {
        $draft = $this->createDraftClientCompany();
        $companyCount = Company::query()->count();
        $field = CompanyField::query()->create(['name' => 'Updated Draft Field']);

        $payload = $this->stepOnePayload([
            'name' => 'شركة تعديل',
            'user_name' => 'draft_update_' . bin2hex(random_bytes(3)),
            'company_field_id' => [$field->id],
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson('/api/v1/companies/' . $draft->id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('payload.id', $draft->id)
            ->assertJsonPath('payload.is_draft', true);

        $this->assertSame($companyCount, Company::query()->count());
        $this->assertDatabaseHas('companies', [
            'id' => $draft->id,
            'user_name' => $payload['user_name'],
            'is_draft' => 1,
        ]);
        $this->assertTrue($draft->fresh()->companyFields()->where('company_fields.id', $field->id)->exists());
    }

    public function test_representative_creation_publishes_draft_company(): void
    {
        $this->makeCurrentTenantReady();
        $draft = $this->createDraftClientCompany();
        $this->makeCompanyOwnerReady($draft);

        $response = $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/company-users/clients/company', [
                'company_id' => $draft->id,
                'name' => 'Client Representative',
                'email' => 'client.rep.' . bin2hex(random_bytes(3)) . '@example.com',
                'phone' => '+20 1000000000',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', [
            'id' => $draft->id,
            'is_draft' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'company_id' => $draft->id,
            'email' => $response->json('payload.email'),
            'is_owner' => 1,
        ]);
    }

    public function test_existing_user_link_still_publishes_draft_company(): void
    {
        $this->makeCurrentTenantReady();
        $draft = $this->createDraftClientCompany();
        $this->makeCompanyOwnerReady($draft);
        $sourceUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_owner' => 0,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/company-users/clients/company', [
                'company_id' => $draft->id,
                'user_id' => $sourceUser->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', [
            'id' => $draft->id,
            'is_draft' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'company_id' => $draft->id,
            'global_company_user_id' => $sourceUser->global_company_user_id,
            'is_owner' => 1,
        ]);
    }
}

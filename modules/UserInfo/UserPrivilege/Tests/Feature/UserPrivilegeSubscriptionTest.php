<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Privilege\Models\Privilege;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Models\Company;
use Modules\MedicalInsurance\Models\MedicalInsurance;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscription;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscriptionFamilyMember;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use Modules\UserInfo\UserPrivilege\Presenters\UserPrivilegePresenter;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionFamilyMemberDTO;
use Modules\MedicalInsurance\Services\MedicalInsuranceSubscriptionCRUDService;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceSubscriptionRepository;
use Stancl\Tenancy\Facades\Tenancy;

/**
 * Comprehensive tests for UserPrivilege + subscription creation flow.
 *
 * Covers the 9 scenarios from the bug report:
 *   1. Create health insurance user privilege with one subscription
 *   2. Create health insurance user privilege with family members
 *   3. Assert subscriptions are saved in DB
 *   4. Assert family members are saved in DB
 *   5. Assert response returns subscriptions, not empty array
 *   6. Assert response returns family_members
 *   7. Assert non-health-insurance privileges do not require subscriptions
 *   8. Assert invalid medical_insurance_id returns validation error
 *   9. Assert transaction rollback if family member creation fails
 */
class UserPrivilegeSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;
    private User $user;
    private Privilege $healthInsurancePrivilege;
    private Privilege $carAllowancePrivilege;
    private MedicalInsurance $medicalInsurance;
    private TypePrivilege $typePrivilege;
    private TypeAllowance $savingTypeAllowance;
    private TypeAllowance $constantTypeAllowance;

    protected function setUp(): void
    {
        parent::setUp();

        Model::unguard();

        // Use an existing country or create one with integer ID
        $countryId = \Modules\Country\Models\Country::first()?->id
            ?? DB::table('countries')->insertGetId(['name' => 'Test Country']);

        // Create company
        $this->company = Company::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Test Company ' . \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'user_name' => 'testcompany',
            'country_id' => $countryId,
        ]);

        Tenancy::initialize($this->company);

        // Create shared records
        $this->healthInsurancePrivilege = Privilege::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Health Insurance',
            'type' => 'health_insurance',
        ]);

        $this->carAllowancePrivilege = Privilege::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Car Allowance',
            'type' => 'car_allowance',
        ]);

        $this->typePrivilege = TypePrivilege::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => ['en' => 'Individual', 'ar' => 'فردي'],
        ]);

        $this->savingTypeAllowance = TypeAllowance::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => ['en' => 'Savings', 'ar' => 'توفير'],
            'code' => 'saving',
        ]);

        $this->constantTypeAllowance = TypeAllowance::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => ['en' => 'Constant', 'ar' => 'ثابت'],
            'code' => 'constant',
        ]);

        // Create user
        $this->user = User::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'company_id' => $this->company->id,
            'global_company_user_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Test User',
            'email' => 'test_' . \Ramsey\Uuid\Uuid::uuid4()->toString() . '@example.com',
        ]);

        // Create medical insurance
        $this->medicalInsurance = MedicalInsurance::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Test Medical Insurance',
            'policy_number' => 'POL-12345',
            'provider' => 'Test Provider',
            'company_id' => $this->company->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'value' => 5000.00,
            'individuals_count' => 5,
            'status' => 1,
        ]);
    }

    // ===============================================================
    // Scenario 1 & 3: Create health insurance privilege with subscription
    // ===============================================================

    public function test_create_health_insurance_privilege_with_subscription_saves_in_db(): void
    {
        $userPrivilege = $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $this->createSubscription();

        $this->assertDatabaseHas('user_privileges', [
            'id' => $userPrivilege->id,
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $this->assertDatabaseHas('medical_insurance_subscriptions', [
            'user_id' => $this->user->id,
            'medical_insurance_id' => $this->medicalInsurance->id,
            'amount' => 3000.00,
            'subscription_no' => 'SUB-INTEGRATION-024',
            'subscription_type' => 'individual',
            'status' => 1,
        ]);
    }

    // ===============================================================
    // Scenario 2 & 4: Create with family members
    // ===============================================================

    public function test_create_health_insurance_privilege_with_family_members_saves_in_db(): void
    {
        $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $familyMembers = [
            new CreateMedicalInsuranceSubscriptionFamilyMemberDTO(
                name: 'فاطمة أحمد',
                nationalId: '1234567890',
                relation: 'زوجة',
                amount: 1500.00,
                subscriptionNo: 'SUB-FAM-TEST-1',
            ),
            new CreateMedicalInsuranceSubscriptionFamilyMemberDTO(
                name: 'محمد أحمد',
                nationalId: '9876543210',
                relation: 'ابن',
                amount: 800.00,
                subscriptionNo: 'SUB-FAM-TEST-2',
            ),
        ];

        $dto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 3000.00,
            subscriptionNo: 'SUB-FAMILY-TEST',
            status: 1,
            subscriptionType: 'family',
            familyMembers: $familyMembers,
        );

        $service = app(MedicalInsuranceSubscriptionCRUDService::class);
        $subscription = $service->create($dto);

        $this->assertDatabaseHas('medical_insurance_subscription_family_members', [
            'medical_insurance_subscription_id' => $subscription->id,
            'name' => 'فاطمة أحمد',
            'national_id' => '1234567890',
            'relation' => 'زوجة',
        ]);

        $this->assertDatabaseHas('medical_insurance_subscription_family_members', [
            'medical_insurance_subscription_id' => $subscription->id,
            'name' => 'محمد أحمد',
            'national_id' => '9876543210',
            'relation' => 'ابن',
        ]);

        $this->assertCount(2, $subscription->familyMembers);
    }

    public function test_family_members_are_persisted_with_correct_data(): void
    {
        $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $memberDto = new CreateMedicalInsuranceSubscriptionFamilyMemberDTO(
            name: 'علي حسن',
            nationalId: '5555555555',
            relation: 'ابن',
            amount: 600.00,
            subscriptionNo: 'SUB-FAM-DETAIL',
        );

        $dto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 2000.00,
            subscriptionNo: 'SUB-FAM-DETAIL-PARENT',
            status: 1,
            subscriptionType: 'family',
            familyMembers: [$memberDto],
        );

        $service = app(MedicalInsuranceSubscriptionCRUDService::class);
        $subscription = $service->create($dto);

        $members = MedicalInsuranceSubscriptionFamilyMember::where(
            'medical_insurance_subscription_id',
            $subscription->id
        )->get();

        $this->assertCount(1, $members);
        $this->assertEquals('علي حسن', $members->first()->name);
        $this->assertEquals('5555555555', $members->first()->national_id);
        $this->assertEquals('ابن', $members->first()->relation);
        $this->assertEquals('600.00', $members->first()->amount);
        $this->assertEquals('SUB-FAM-DETAIL', $members->first()->subscription_no);
    }

    // ===============================================================
    // Scenario 5: Response returns subscriptions (not empty array)
    // ===============================================================

    public function test_presenter_returns_subscriptions_for_health_insurance(): void
    {
        $userPrivilege = $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $subscription = $this->createSubscription();

        $subscriptionsByInsurance = [
            $this->medicalInsurance->id => [$subscription],
        ];

        $userPrivilege->load(['privilege', 'typePrivilege', 'typeAllowance', 'medicalInsurance']);

        $presenter = new UserPrivilegePresenter($userPrivilege, $subscriptionsByInsurance);
        $data = $presenter->getData();

        $this->assertArrayHasKey('subscriptions', $data, 'Health insurance must have subscriptions key');
        $this->assertNotEmpty($data['subscriptions'], 'subscriptions should not be empty');
        $this->assertCount(1, $data['subscriptions']);

        $sub = $data['subscriptions'][0];
        $this->assertEquals($this->medicalInsurance->id, $sub['medical_insurance_id']);
        $this->assertEquals(3000.00, $sub['amount']);
        $this->assertEquals('SUB-INTEGRATION-024', $sub['subscription_no']);
        $this->assertEquals('individual', $sub['subscription_type']);
        $this->assertEquals(1, $sub['status']);
    }

    // ===============================================================
    // Scenario 6: Response returns family_members inside subscriptions
    // ===============================================================

    public function test_presenter_returns_family_members_in_subscriptions(): void
    {
        $userPrivilege = $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $memberDto = new CreateMedicalInsuranceSubscriptionFamilyMemberDTO(
            name: 'سارة محمد',
            nationalId: '1111111111',
            relation: 'زوجة',
            amount: 1500.00,
            subscriptionNo: 'SUB-FAM-PRESENTER',
        );

        $dto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 3000.00,
            subscriptionNo: 'SUB-FAM-PRESENTER-PARENT',
            status: 1,
            subscriptionType: 'family',
            familyMembers: [$memberDto],
        );

        $service = app(MedicalInsuranceSubscriptionCRUDService::class);
        $subscription = $service->create($dto);

        $subscriptionsByInsurance = [
            $this->medicalInsurance->id => [$subscription],
        ];

        $userPrivilege->load(['privilege', 'typePrivilege', 'typeAllowance', 'medicalInsurance']);

        $presenter = new UserPrivilegePresenter($userPrivilege, $subscriptionsByInsurance);
        $data = $presenter->getData();

        $this->assertNotEmpty($data['subscriptions']);
        $sub = $data['subscriptions'][0];

        $this->assertArrayHasKey('family_members', $sub);
        $this->assertNotEmpty($sub['family_members'], 'family_members should not be empty');
        $this->assertCount(1, $sub['family_members']);

        $member = $sub['family_members'][0];
        $this->assertEquals('سارة محمد', $member['name']);
        $this->assertEquals('1111111111', $member['national_id']);
        $this->assertEquals('زوجة', $member['relation']);
        $this->assertEquals(1500.00, $member['amount']);
        $this->assertEquals('SUB-FAM-PRESENTER', $member['subscription_no']);
    }

    // ===============================================================
    // Scenario 7: Non-health-insurance privileges do not require subscriptions
    // ===============================================================

    public function test_non_health_insurance_privilege_does_not_return_subscriptions_key(): void
    {
        $userPrivilege = $this->createUserPrivilege([
            'privilege_id' => $this->carAllowancePrivilege->id,
        ]);

        $userPrivilege->load(['privilege', 'typePrivilege', 'typeAllowance', 'medicalInsurance']);

        $presenter = new UserPrivilegePresenter($userPrivilege, []);
        $data = $presenter->getData();

        $this->assertArrayNotHasKey('subscriptions', $data);
        $this->assertNull($data['medical_insurance']);
    }

    // ===============================================================
    // Scenario 8: Validation errors for invalid data
    // ===============================================================

    public function test_invalid_medical_insurance_id_fails_validation(): void
    {
        $request = new \Modules\UserInfo\UserPrivilege\Requests\CreateUserPrivilegeRequest();

        $request->merge([
            'user_id' => $this->user->id,
            'privilege_id' => $this->healthInsurancePrivilege->id,
            'type_privilege_id' => $this->typePrivilege->id,
            'type_allowance_code' => 'saving',
            'description' => 'Test',
            'subscriptions' => [
                [
                    'medical_insurance_id' => '00000000-0000-0000-0000-000000000000',
                    'amount' => 3000,
                    'subscription_no' => 'SUB-VALIDATION-400',
                    'status' => 1,
                ],
            ],
        ]);

        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'subscriptions.0.medical_insurance_id',
            $validator->errors()->toArray()
        );
    }

    public function test_subscriptions_required_fields_validation(): void
    {
        $request = new \Modules\UserInfo\UserPrivilege\Requests\CreateUserPrivilegeRequest();

        $request->merge([
            'user_id' => $this->user->id,
            'privilege_id' => $this->healthInsurancePrivilege->id,
            'type_privilege_id' => $this->typePrivilege->id,
            'type_allowance_code' => 'saving',
            'subscriptions' => [
                [
                    // Missing medical_insurance_id, amount, subscription_no
                ],
            ],
        ]);

        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('subscriptions.0.medical_insurance_id', $errors);
        $this->assertArrayHasKey('subscriptions.0.amount', $errors);
        $this->assertArrayHasKey('subscriptions.0.subscription_no', $errors);
    }

    // ===============================================================
    // Scenario 9: Transaction rollback
    // ===============================================================

    public function test_transaction_rollback_when_family_member_creation_fails(): void
    {
        $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        // Name exceeds varchar(255) — should cause a DB error and rollback
        $memberDto = new CreateMedicalInsuranceSubscriptionFamilyMemberDTO(
            name: str_repeat('X', 300),
            nationalId: '1234567890',
            relation: 'زوجة',
            amount: 1500.00,
            subscriptionNo: 'SUB-ROLLBACK-TEST',
        );

        $dto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 3000.00,
            subscriptionNo: 'SUB-ROLLBACK-TEST',
            status: 1,
            subscriptionType: 'family',
            familyMembers: [$memberDto],
        );

        $service = app(MedicalInsuranceSubscriptionCRUDService::class);

        $exceptionThrown = false;
        try {
            $service->create($dto);
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Expected exception when family member name exceeds limit');

        // Both subscription and family members should be rolled back
        $this->assertDatabaseMissing('medical_insurance_subscriptions', [
            'subscription_no' => 'SUB-ROLLBACK-TEST',
        ]);

        $this->assertDatabaseMissing('medical_insurance_subscription_family_members', [
            'subscription_no' => 'SUB-ROLLBACK-TEST',
        ]);
    }

    public function test_create_many_transaction_rollback_on_failure(): void
    {
        $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $validDto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 1000.00,
            subscriptionNo: 'SUB-BATCH-VALID',
            status: 1,
            subscriptionType: 'individual',
            familyMembers: [],
        );

        // Non-existent FK reference — will cause constraint violation
        $invalidDto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: '00000000-0000-0000-0000-000000000000',
            amount: 2000.00,
            subscriptionNo: 'SUB-BATCH-INVALID',
            status: 1,
            subscriptionType: 'individual',
            familyMembers: [],
        );

        $service = app(MedicalInsuranceSubscriptionCRUDService::class);

        $exceptionThrown = false;
        try {
            $service->createMany([$validDto, $invalidDto]);
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Expected FK constraint violation');

        // The valid subscription should also be rolled back
        $this->assertDatabaseMissing('medical_insurance_subscriptions', [
            'subscription_no' => 'SUB-BATCH-VALID',
        ]);
    }

    // ===============================================================
    // Repository filter fix: specific user queries bypass constant filter
    // ===============================================================

    public function test_list_subscriptions_returns_results_for_specific_user_regardless_of_constant_allowance(): void
    {
        // Give the user a constant allowance privilege
        UserPrivilege::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'company_id' => $this->company->id,
            'global_id' => $this->user->global_company_user_id,
            'privilege_id' => $this->healthInsurancePrivilege->id,
            'type_privilege_id' => $this->typePrivilege->id,
            'type_allowance_code' => 'constant',
            'charge_amount' => '1000',
            'description' => 'Constant allowance',
        ]);

        // Create a subscription for the same user
        $this->createUserPrivilege([
            'privilege_id' => $this->healthInsurancePrivilege->id,
        ]);

        $dto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 3000.00,
            subscriptionNo: 'SUB-CONSTANT-FILTER-TEST',
            status: 1,
            subscriptionType: 'individual',
            familyMembers: [],
        );

        $service = app(MedicalInsuranceSubscriptionCRUDService::class);
        $service->create($dto);

        // Query by specific user_id — should return results despite constant allowance
        $repo = app(MedicalInsuranceSubscriptionRepository::class);
        $result = $repo->listSubscriptions(1, 100, ['user_id' => $this->user->id]);

        $this->assertNotEmpty(
            $result['data'],
            'Subscriptions should be returned for a specific user even if they have a constant allowance'
        );
        $this->assertEquals(
            'SUB-CONSTANT-FILTER-TEST',
            $result['data'][0]->subscription_no
        );
    }

    // ===============================================================
    // Helpers
    // ===============================================================

    private function createUserPrivilege(array $overrides = []): UserPrivilege
    {
        $data = array_merge([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'company_id' => $this->company->id,
            'global_id' => $this->user->global_company_user_id,
            'privilege_id' => $this->healthInsurancePrivilege->id,
            'type_privilege_id' => $this->typePrivilege->id,
            'type_allowance_code' => 'saving',
            'charge_amount' => '500',
            'description' => 'Test privilege',
        ], $overrides);

        return UserPrivilege::create($data);
    }

    private function createSubscription(): MedicalInsuranceSubscription
    {
        $dto = new CreateMedicalInsuranceSubscriptionDTO(
            userId: $this->user->id,
            medicalInsuranceId: $this->medicalInsurance->id,
            amount: 3000.00,
            subscriptionNo: 'SUB-INTEGRATION-024',
            status: 1,
            subscriptionType: 'individual',
            familyMembers: [],
        );

        return app(MedicalInsuranceSubscriptionCRUDService::class)->create($dto);
    }
}

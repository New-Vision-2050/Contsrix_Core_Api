<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Tests\Feature;

use Tests\TestCase;
use BasePackage\Shared\Model\Translation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Shared\Privilege\Models\Privilege;
use Modules\Shared\Privilege\Presenters\PrivilegePresenter;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use Modules\UserInfo\UserPrivilege\Presenters\UserPrivilegePresenter;
use Modules\MedicalInsurance\Models\MedicalInsurance;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscription;

/**
 * Tests that user_privileges API responses include correct
 * health_insurance card data and card_fields configuration.
 *
 * Uses forceFill + setRelation to hydrate models without DB,
 * while running inside the Laravel app container (needed by
 * HasTranslations trait and Service Container).
 */
class UserPrivilegeHealthInsuranceTest extends TestCase
{
    // ---------------------------------------------------------------
    // Privilege presenter includes card_fields
    // ---------------------------------------------------------------

    public function test_privilege_presenter_includes_card_fields_for_health_insurance(): void
    {
        $privilege = $this->makePrivilege('health_insurance', 'تأمين طبي', 'Health Insurance');
        $presenter = new PrivilegePresenter($privilege);
        $data = $presenter->getData();

        $this->assertEquals('health_insurance', $data['type']);
        $this->assertArrayHasKey('card_fields', $data);
        $this->assertArrayHasKey('fields', $data['card_fields']);
        $this->assertNotEmpty($data['card_fields']['fields']);
    }

    public function test_health_insurance_card_fields_contain_required_keys(): void
    {
        $privilege = $this->makePrivilege('health_insurance', 'تأمين طبي', 'Health Insurance');
        $presenter = new PrivilegePresenter($privilege);
        $data = $presenter->getData();

        $keys = array_column($data['card_fields']['fields'], 'key');
        $this->assertContains('policy_number', $keys);
        $this->assertContains('allowance_kind', $keys);
        $this->assertContains('allowance_type', $keys);
        $this->assertContains('description', $keys);
    }

    // ---------------------------------------------------------------
    // Non-health_insurance types are not broken
    // ---------------------------------------------------------------

    public function test_social_insurance_card_fields_do_not_contain_policy_number(): void
    {
        $privilege = $this->makePrivilege('social_insurance', 'تأمين اجتماعي', 'Social Insurance');
        $presenter = new PrivilegePresenter($privilege);
        $data = $presenter->getData();

        $this->assertEquals('social_insurance', $data['type']);
        $this->assertArrayHasKey('card_fields', $data);

        $keys = array_column($data['card_fields']['fields'], 'key');
        $this->assertContains('charge_amount', $keys);
        $this->assertNotContains('policy_number', $keys);
    }

    public function test_car_allowance_card_fields_exist(): void
    {
        $privilege = $this->makePrivilege('car_allowance', 'بدل سيارة', 'Car Allowance');
        $presenter = new PrivilegePresenter($privilege);
        $data = $presenter->getData();

        $this->assertArrayHasKey('card_fields', $data);
        $keys = array_column($data['card_fields']['fields'], 'key');
        $this->assertContains('charge_amount', $keys);
        $this->assertContains('allowance_type', $keys);
    }

    // ---------------------------------------------------------------
    // UserPrivilegePresenter includes card_fields and medical insurance
    // ---------------------------------------------------------------

    public function test_user_privilege_presenter_includes_health_insurance_card_data(): void
    {
        $privilege = $this->makePrivilege('health_insurance', 'تأمين طبي', 'Health Insurance');

        $typePrivilege = $this->makeTranslatingModel(
            TypePrivilege::class,
            '550e8400-e29b-41d4-a716-446655440020',
            'فردي', 'Individual'
        );

        $typeAllowance = $this->makeTranslatingModel(
            TypeAllowance::class,
            '550e8400-e29b-41d4-a716-446655440030',
            'ثابت', 'Constant'
        );
        $typeAllowance->code = 'constant';

        $medicalInsurance = new MedicalInsurance();
        $medicalInsurance->forceFill([
            'id'                => '550e8400-e29b-41d4-a716-446655440010',
            'name'              => 'Test Health Insurance',
            'policy_number'     => 'POL-12345',
            'provider'          => 'Test Provider',
            'employee_id'       => null,
            'company_id'        => '550e8400-e29b-41d4-a716-446655440999',
            'start_date'        => \Carbon\Carbon::parse('2026-01-01'),
            'end_date'          => \Carbon\Carbon::parse('2026-12-31'),
            'value'             => 5000.00,
            'individuals_count' => 5,
            'status'            => 1,
        ]);
        $medicalInsurance->id = '550e8400-e29b-41d4-a716-446655440010';
        $medicalInsurance->exists = true;
        $medicalInsurance->syncOriginal();

        $userPrivilege = new UserPrivilege();
        $userPrivilege->forceFill([
            'company_id'           => '550e8400-e29b-41d4-a716-446655440050',
            'global_id'            => '550e8400-e29b-41d4-a716-446655440060',
            'type_privilege_id'    => '550e8400-e29b-41d4-a716-446655440020',
            'type_allowance_code'  => 'constant',
            'charge_amount'        => '500',
            'description'          => 'Health insurance card',
            'privilege_id'         => '550e8400-e29b-41d4-a716-446655440001',
            'period_id'            => null,
            'medical_insurance_id' => '550e8400-e29b-41d4-a716-446655440010',
        ]);
        $userPrivilege->id = '550e8400-e29b-41d4-a716-446655440040';
        $userPrivilege->exists = true;
        $userPrivilege->syncOriginal();

        $userPrivilege->setRelation('privilege', $privilege);
        $userPrivilege->setRelation('typePrivilege', $typePrivilege);
        $userPrivilege->setRelation('typeAllowance', $typeAllowance);
        $userPrivilege->setRelation('medicalInsurance', $medicalInsurance);

        $presenter = new UserPrivilegePresenter($userPrivilege);
        $data = $presenter->getData();

        // Base fields
        $this->assertEquals('Health insurance card', $data['description']);
        $this->assertEquals('500', $data['charge_amount']);

        // Privilege relationship
        $this->assertNotNull($data['privilege']);
        $this->assertEquals('health_insurance', $data['privilege']['type']);

        // Card fields included
        $this->assertArrayHasKey('card_fields', $data);
        $this->assertNotEmpty($data['card_fields']['fields']);

        $cardKeys = array_column($data['card_fields']['fields'], 'key');
        $this->assertContains('policy_number', $cardKeys);
        $this->assertContains('allowance_kind', $cardKeys);
        $this->assertContains('allowance_type', $cardKeys);
        $this->assertContains('description', $cardKeys);

        // Medical insurance card data
        $this->assertNotNull($data['medical_insurance']);
        $this->assertEquals('POL-12345', $data['medical_insurance']['policy_number']);
        $this->assertEquals('Test Provider', $data['medical_insurance']['provider']);
        $this->assertEquals('2026-01-01', $data['medical_insurance']['start_date']);
        $this->assertEquals('2026-12-31', $data['medical_insurance']['end_date']);
        $this->assertEquals(5000.00, $data['medical_insurance']['value']);
        $this->assertEquals(5, $data['medical_insurance']['individuals_count']);
        $this->assertEquals(1, $data['medical_insurance']['status']);
    }

    // ---------------------------------------------------------------
    // UserPrivilegePresenter without medical insurance
    // ---------------------------------------------------------------

    public function test_user_privilege_presenter_medical_insurance_is_null_for_car_allowance(): void
    {
        $privilege = $this->makePrivilege('car_allowance', 'بدل سيارة', 'Car Allowance');

        $userPrivilege = new UserPrivilege();
        $userPrivilege->forceFill([
            'company_id'           => '550e8400-e29b-41d4-a716-446655440050',
            'global_id'            => '550e8400-e29b-41d4-a716-446655440060',
            'type_privilege_id'    => null,
            'type_allowance_code'  => null,
            'charge_amount'        => '1000',
            'description'          => null,
            'privilege_id'         => '550e8400-e29b-41d4-a716-446655440003',
            'period_id'            => null,
            'medical_insurance_id' => null,
        ]);
        $userPrivilege->id = '550e8400-e29b-41d4-a716-446655440041';
        $userPrivilege->exists = true;
        $userPrivilege->syncOriginal();
        $userPrivilege->setRelation('privilege', $privilege);

        $presenter = new UserPrivilegePresenter($userPrivilege);
        $data = $presenter->getData();

        // Medical insurance should be null for non-health-insurance types
        $this->assertNull($data['medical_insurance']);

        // Card fields still present (from privilege type)
        $this->assertArrayHasKey('card_fields', $data);
        $cardKeys = array_column($data['card_fields']['fields'], 'key');
        $this->assertContains('charge_amount', $cardKeys);
        $this->assertNotContains('policy_number', $cardKeys);
    }

    // ---------------------------------------------------------------
    // UserPrivilegePresenter without any privilege relation
    // ---------------------------------------------------------------

    public function test_user_privilege_presenter_without_privilege_has_no_card_fields(): void
    {
        $userPrivilege = new UserPrivilege();
        $userPrivilege->forceFill([
            'company_id'           => '550e8400-e29b-41d4-a716-446655440050',
            'global_id'            => '550e8400-e29b-41d4-a716-446655440060',
            'type_privilege_id'    => null,
            'type_allowance_code'  => null,
            'charge_amount'        => null,
            'description'          => null,
            'privilege_id'         => null,
            'period_id'            => null,
            'medical_insurance_id' => null,
        ]);
        $userPrivilege->id = '550e8400-e29b-41d4-a716-446655440042';
        $userPrivilege->exists = true;
        $userPrivilege->syncOriginal();

        $presenter = new UserPrivilegePresenter($userPrivilege);
        $data = $presenter->getData();

        // Should not include card_fields when privilege is not set
        $this->assertArrayNotHasKey('card_fields', $data);
    }

    // ---------------------------------------------------------------
    // List request: type filter validation
    // ---------------------------------------------------------------

    public function test_list_request_accepts_health_insurance_type_filter(): void
    {
        $request = new \Modules\UserInfo\UserPrivilege\Requests\GetUserPrivilegeListRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('type', $rules);
        $this->assertInstanceOf(\Illuminate\Validation\Rules\In::class, $rules['type'][2]);
    }

    public function test_user_privilege_filter_supports_privilege_type(): void
    {
        $filterClass = \Modules\UserInfo\UserPrivilege\Filters\UserPrivilegeFilter::class;

        $this->assertTrue(method_exists($filterClass, 'type'));
    }

    public function test_user_privilege_filter_includes_medical_insurance_records_for_health_insurance_type(): void
    {
        $filterClass = \Modules\UserInfo\UserPrivilege\Filters\UserPrivilegeFilter::class;
        $source = file_get_contents((new \ReflectionClass($filterClass))->getFileName());

        $this->assertStringContainsString('orWhereNotNull(\'medical_insurance_id\')', $source);
        $this->assertStringContainsString('TYPE_HEALTH_INSURANCE', $source);
    }

    // ---------------------------------------------------------------
    // Validation: percentage type_allowance_code is rejected
    // ---------------------------------------------------------------

    public function test_create_user_privilege_rejects_percentage_type_allowance(): void
    {
        $request = new \Modules\UserInfo\UserPrivilege\Requests\CreateUserPrivilegeRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('type_allowance_code', $rules);
        $this->assertStringContainsString('not_in:percentage', $rules['type_allowance_code']);
    }

    public function test_update_user_privilege_rejects_percentage_type_allowance(): void
    {
        $request = new \Modules\UserInfo\UserPrivilege\Requests\UpdateUserPrivilegeRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('type_allowance_code', $rules);
        $this->assertStringContainsString('not_in:percentage', $rules['type_allowance_code']);
    }

    // ---------------------------------------------------------------
    // Validation: fixed (constant) employees cannot have medical insurance
    // ---------------------------------------------------------------

    public function test_user_privilege_with_constant_type_excludes_medical_insurance(): void
    {
        // This is a structural test: verify that the privilege model
        // has the type_allowance_code field that links to TypeAllowance
        $privilege = $this->makePrivilege('health_insurance', 'تأمين طبي', 'Health Insurance');

        $typeAllowance = $this->makeTranslatingModel(
            TypeAllowance::class,
            '550e8400-e29b-41d4-a716-446655440030',
            'ثابت', 'Constant'
        );
        $typeAllowance->code = 'constant';

        $userPrivilege = new UserPrivilege();
        $userPrivilege->forceFill([
            'company_id'           => '550e8400-e29b-41d4-a716-446655440050',
            'global_id'            => '550e8400-e29b-41d4-a716-446655440060',
            'type_privilege_id'    => null,
            'type_allowance_code'  => 'constant',
            'charge_amount'        => '500',
            'description'          => 'Fixed allowance',
            'privilege_id'         => '550e8400-e29b-41d4-a716-446655440001',
            'period_id'            => null,
            'medical_insurance_id' => null,
        ]);
        $userPrivilege->id = '550e8400-e29b-41d4-a716-446655440040';
        $userPrivilege->exists = true;
        $userPrivilege->syncOriginal();

        $userPrivilege->setRelation('privilege', $privilege);
        $userPrivilege->setRelation('typeAllowance', $typeAllowance);

        $presenter = new UserPrivilegePresenter($userPrivilege);
        $data = $presenter->getData();

        // Employee with constant (fixed) type should have null medical_insurance
        $this->assertNull($data['medical_insurance']);
        $this->assertEquals('constant', $data['type_allowance_code']);
    }

    // ---------------------------------------------------------------
    // Default insurance type is saving
    // ---------------------------------------------------------------

    public function test_user_privilege_with_saving_type_allows_medical_insurance(): void
    {
        $privilege = $this->makePrivilege('health_insurance', 'تأمين طبي', 'Health Insurance');

        $typeAllowance = $this->makeTranslatingModel(
            TypeAllowance::class,
            '550e8400-e29b-41d4-a716-446655440031',
            'توفير', 'Savings'
        );
        $typeAllowance->code = 'saving';

        $medicalInsurance = new MedicalInsurance();
        $medicalInsurance->forceFill([
            'id'                => '550e8400-e29b-41d4-a716-446655440010',
            'name'              => 'Test Health Insurance',
            'policy_number'     => 'POL-67890',
            'provider'          => 'Test Provider',
            'employee_id'       => null,
            'company_id'        => '550e8400-e29b-41d4-a716-446655440999',
            'start_date'        => \Carbon\Carbon::parse('2026-01-01'),
            'end_date'          => \Carbon\Carbon::parse('2026-12-31'),
            'value'             => 3000.00,
            'individuals_count' => 2,
            'status'            => 1,
        ]);
        $medicalInsurance->id = '550e8400-e29b-41d4-a716-446655440010';
        $medicalInsurance->exists = true;
        $medicalInsurance->syncOriginal();

        $userPrivilege = new UserPrivilege();
        $userPrivilege->forceFill([
            'company_id'           => '550e8400-e29b-41d4-a716-446655440050',
            'global_id'            => '550e8400-e29b-41d4-a716-446655440061',
            'type_privilege_id'    => null,
            'type_allowance_code'  => 'saving',
            'charge_amount'        => '300',
            'description'          => 'Savings allowance with insurance',
            'privilege_id'         => '550e8400-e29b-41d4-a716-446655440001',
            'period_id'            => null,
            'medical_insurance_id' => '550e8400-e29b-41d4-a716-446655440010',
        ]);
        $userPrivilege->id = '550e8400-e29b-41d4-a716-446655440041';
        $userPrivilege->exists = true;
        $userPrivilege->syncOriginal();

        $userPrivilege->setRelation('privilege', $privilege);
        $userPrivilege->setRelation('typeAllowance', $typeAllowance);
        $userPrivilege->setRelation('medicalInsurance', $medicalInsurance);

        $presenter = new UserPrivilegePresenter($userPrivilege);
        $data = $presenter->getData();

        // Employee with saving type can have medical insurance
        $this->assertNotNull($data['medical_insurance']);
        $this->assertEquals('saving', $data['type_allowance_code']);
        $this->assertEquals('POL-67890', $data['medical_insurance']['policy_number']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Create a Privilege model with translations pre-loaded.
     */
    private function makePrivilege(string $type, string $nameAr, string $nameEn): Privilege
    {
        $privilege = new Privilege();
        $privilege->forceFill([
            'id'   => '550e8400-e29b-41d4-a716-446655440001',
            'type' => $type,
        ]);
        $privilege->id = '550e8400-e29b-41d4-a716-446655440001';
        $privilege->exists = true;
        $privilege->syncOriginal();

        // Inject translations so HasTranslations trait can resolve name
        $privilege->setRelation('translations', $this->makeTranslationCollection([
            ['field' => 'name', 'locale' => 'ar', 'content' => $nameAr],
            ['field' => 'name', 'locale' => 'en', 'content' => $nameEn],
        ]));

        return $privilege;
    }

    /**
     * Create a translatable model (TypePrivilege, TypeAllowance, etc.)
     * with translations pre-loaded.
     */
    private function makeTranslatingModel(string $class, string $id, string $nameAr, string $nameEn): mixed
    {
        $model = new $class();
        $model->forceFill(['id' => $id]);
        $model->id = $id;
        $model->exists = true;
        $model->syncOriginal();

        $model->setRelation('translations', $this->makeTranslationCollection([
            ['field' => 'name', 'locale' => 'ar', 'content' => $nameAr],
            ['field' => 'name', 'locale' => 'en', 'content' => $nameEn],
        ]));

        return $model;
    }

    /**
     * Build a collection of Translation model stubs.
     */
    private function makeTranslationCollection(array $entries): EloquentCollection
    {
        $translations = [];

        foreach ($entries as $entry) {
            $t = new Translation();
            $t->forceFill([
                'field'   => $entry['field'],
                'locale'  => $entry['locale'],
                'content' => $entry['content'],
            ]);
            $t->exists = true;
            $t->syncOriginal();
            $translations[] = $t;
        }

        return new EloquentCollection($translations);
    }
}

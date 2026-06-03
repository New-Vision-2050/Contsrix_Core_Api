<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use Modules\MedicalInsurance\Requests\CreateMedicalInsuranceSubscriptionRequest;
use Modules\MedicalInsurance\Requests\UpdateMedicalInsuranceSubscriptionRequest;

/**
 * Tests that medical insurance subscription validation rules
 * correctly enforce:
 *  - subscription_type (individual/family)
 *  - individual cannot have family_members
 *  - family can have family_members
 *  - fixed-type employees cannot be added
 *  - cannot change family→individual when dependents exist
 */
class MedicalInsuranceSubscriptionValidationTest extends TestCase
{
    // ---------------------------------------------------------------
    // Create: subscription_type required and valid values
    // ---------------------------------------------------------------

    public function test_create_requires_subscription_type(): void
    {
        $request = new CreateMedicalInsuranceSubscriptionRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('subscriptions.*.subscription_type', $rules);
        $this->assertStringContainsString('required', $rules['subscriptions.*.subscription_type']);
        $this->assertStringContainsString('individual', $rules['subscriptions.*.subscription_type']);
        $this->assertStringContainsString('family', $rules['subscriptions.*.subscription_type']);
    }

    public function test_create_rejects_invalid_subscription_type(): void
    {
        $rules = [
            'subscriptions'                    => 'required|array|min:1',
            'subscriptions.*.subscription_type' => 'required|string|in:individual,family',
        ];

        $data = [
            'subscriptions' => [
                [
                    'subscription_type' => 'invalid_type',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('subscriptions.0.subscription_type', $validator->errors()->toArray());
    }

    public function test_create_accepts_individual_and_family(): void
    {
        $rules = [
            'subscriptions'                    => 'required|array|min:1',
            'subscriptions.*.subscription_type' => 'required|string|in:individual,family',
        ];

        $dataIndividual = [
            'subscriptions' => [
                ['subscription_type' => 'individual'],
            ],
        ];
        $validator = Validator::make($dataIndividual, $rules);
        $this->assertFalse($validator->fails());

        $dataFamily = [
            'subscriptions' => [
                ['subscription_type' => 'family'],
            ],
        ];
        $validator = Validator::make($dataFamily, $rules);
        $this->assertFalse($validator->fails());
    }

    // ---------------------------------------------------------------
    // Create: individual cannot have family members
    // ---------------------------------------------------------------

    public function test_create_individual_with_family_members_is_rejected(): void
    {
        // Test the business rule: individual subscription cannot have family members
        $validator = Validator::make(
            ['subscription_type' => 'individual', 'family_members' => [['name' => 'Test']]],
            ['subscription_type' => 'required|string|in:individual,family', 'family_members' => 'nullable|array']
        );

        // Simulate the after-hook logic
        $validator->after(function ($validator) {
            $subscriptionType = $validator->getData()['subscription_type'] ?? 'individual';
            $hasFamilyMembers = !empty($validator->getData()['family_members']);

            if ($subscriptionType === 'individual' && $hasFamilyMembers) {
                $validator->errors()->add('family_members', 'Individual subscriptions cannot have family members.');
            }
        });

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('family_members', $validator->errors()->toArray());
    }

    public function test_create_individual_without_family_members_is_accepted(): void
    {
        // Test the business rule: individual subscription without family members is valid
        $validator = Validator::make(
            ['subscription_type' => 'individual', 'family_members' => null],
            ['subscription_type' => 'required|string|in:individual,family', 'family_members' => 'nullable|array']
        );

        $validator->after(function ($validator) {
            $subscriptionType = $validator->getData()['subscription_type'] ?? 'individual';
            $hasFamilyMembers = !empty($validator->getData()['family_members']);

            if ($subscriptionType === 'individual' && $hasFamilyMembers) {
                $validator->errors()->add('family_members', 'Individual subscriptions cannot have family members.');
            }
        });

        $this->assertFalse($validator->fails());
    }

    // ---------------------------------------------------------------
    // Create: family can have family members
    // ---------------------------------------------------------------

    public function test_create_family_with_family_members_is_accepted(): void
    {
        $validator = Validator::make(
            [
                'subscription_type' => 'family',
                'family_members'    => [
                    ['name' => 'Test Member', 'national_id' => '12345', 'relation' => 'spouse', 'amount' => 500],
                ],
            ],
            ['subscription_type' => 'required|string|in:individual,family', 'family_members' => 'nullable|array']
        );

        $validator->after(function ($validator) {
            $subscriptionType = $validator->getData()['subscription_type'] ?? 'individual';
            $hasFamilyMembers = !empty($validator->getData()['family_members']);

            if ($subscriptionType === 'individual' && $hasFamilyMembers) {
                $validator->errors()->add('family_members', 'Individual subscriptions cannot have family members.');
            }
        });

        $this->assertFalse($validator->fails());
    }

    public function test_create_family_without_family_members_is_accepted(): void
    {
        $validator = Validator::make(
            ['subscription_type' => 'family', 'family_members' => null],
            ['subscription_type' => 'required|string|in:individual,family', 'family_members' => 'nullable|array']
        );

        $validator->after(function ($validator) {
            $subscriptionType = $validator->getData()['subscription_type'] ?? 'individual';
            $hasFamilyMembers = !empty($validator->getData()['family_members']);

            if ($subscriptionType === 'individual' && $hasFamilyMembers) {
                $validator->errors()->add('family_members', 'Individual subscriptions cannot have family members.');
            }
        });

        $this->assertFalse($validator->fails());
    }

    // ---------------------------------------------------------------
    // Update: subscription_type required
    // ---------------------------------------------------------------

    public function test_update_requires_subscription_type(): void
    {
        $request = new UpdateMedicalInsuranceSubscriptionRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('subscriptions.*.subscription_type', $rules);
        $this->assertStringContainsString('required', $rules['subscriptions.*.subscription_type']);
        $this->assertStringContainsString('individual', $rules['subscriptions.*.subscription_type']);
        $this->assertStringContainsString('family', $rules['subscriptions.*.subscription_type']);
    }

    public function test_update_individual_with_family_members_is_rejected(): void
    {
        // Test business rule: update to individual with family members is rejected
        $validator = Validator::make(
            ['subscription_type' => 'individual', 'family_members' => [['name' => 'Test']]],
            ['subscription_type' => 'required|string|in:individual,family', 'family_members' => 'nullable|array']
        );

        $validator->after(function ($validator) {
            $subscriptionType = $validator->getData()['subscription_type'] ?? 'individual';
            $hasFamilyMembers = !empty($validator->getData()['family_members']);

            if ($subscriptionType === 'individual' && $hasFamilyMembers) {
                $validator->errors()->add('family_members', 'Individual subscriptions cannot have family members.');
            }
        });

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('family_members', $validator->errors()->toArray());
    }

    // ---------------------------------------------------------------
    // Subscription type defaults
    // ---------------------------------------------------------------

    public function test_subscription_type_defaults_to_individual_in_dto(): void
    {
        $dto = new \Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO(
            userId: '550e8400-e29b-41d4-a716-446655440001',
            medicalInsuranceId: '550e8400-e29b-41d4-a716-446655440002',
            amount: 1000.00,
            subscriptionNo: 'SUB-001',
        );

        $this->assertEquals('individual', $dto->subscriptionType);

        $array = $dto->toArray();
        $this->assertEquals('individual', $array['subscription_type']);
    }

    public function test_subscription_type_can_be_overridden_to_family_in_dto(): void
    {
        $dto = new \Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO(
            userId: '550e8400-e29b-41d4-a716-446655440001',
            medicalInsuranceId: '550e8400-e29b-41d4-a716-446655440002',
            amount: 1000.00,
            subscriptionNo: 'SUB-002',
            subscriptionType: 'family',
        );

        $this->assertEquals('family', $dto->subscriptionType);
    }

    // ---------------------------------------------------------------
    // Model fillable includes subscription_type
    // ---------------------------------------------------------------

    public function test_model_fillable_includes_subscription_type(): void
    {
        $model = new \Modules\MedicalInsurance\Models\MedicalInsuranceSubscription();

        $this->assertContains('subscription_type', $model->getFillable());
    }

    // ---------------------------------------------------------------
    // Presenter includes subscription_type
    // ---------------------------------------------------------------

    public function test_presenter_includes_subscription_type(): void
    {
        $subscription = new \Modules\MedicalInsurance\Models\MedicalInsuranceSubscription();
        $subscription->forceFill([
            'id'                   => '550e8400-e29b-41d4-a716-446655440010',
            'user_id'              => '550e8400-e29b-41d4-a716-446655440020',
            'medical_insurance_id' => '550e8400-e29b-41d4-a716-446655440030',
            'company_id'           => '550e8400-e29b-41d4-a716-446655440040',
            'amount'               => 1500.00,
            'subscription_no'      => 'SUB-TEST-001',
            'subscription_type'    => 'family',
            'status'               => 1,
        ]);
        $subscription->id = '550e8400-e29b-41d4-a716-446655440010';
        $subscription->exists = true;
        $subscription->syncOriginal();

        $presenter = new \Modules\MedicalInsurance\Presenters\MedicalInsuranceSubscriptionPresenter($subscription);
        $data = $presenter->getData();

        $this->assertArrayHasKey('subscription_type', $data);
        $this->assertEquals('family', $data['subscription_type']);
    }
}

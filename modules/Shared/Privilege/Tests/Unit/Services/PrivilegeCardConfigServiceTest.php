<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Tests\Unit\Services;

use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;
use PHPUnit\Framework\TestCase;

class PrivilegeCardConfigServiceTest extends TestCase
{
    private PrivilegeCardConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PrivilegeCardConfigService();
    }

    // ---------------------------------------------------------------
    // Health Insurance — the primary target of this task
    // ---------------------------------------------------------------

    public function test_health_insurance_returns_correct_card_config(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE);

        $this->assertArrayHasKey('fields', $config);
        $this->assertNotEmpty($config['fields']);

        $keys = array_column($config['fields'], 'key');

        $this->assertContains('policy_number', $keys);
        $this->assertContains('allowance_kind', $keys);
        $this->assertContains('allowance_type', $keys);
        $this->assertContains('description', $keys);
    }

    public function test_health_insurance_policy_number_is_required(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE);

        $policyField = $this->findFieldByKey($config['fields'], 'policy_number');
        $this->assertNotNull($policyField);
        $this->assertTrue($policyField['required']);
        $this->assertEquals('text', $policyField['type']);
        $this->assertEquals('medical_insurance.policy_number', $policyField['source']);
    }

    public function test_health_insurance_allowance_kind_has_correct_source(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE);

        $kindField = $this->findFieldByKey($config['fields'], 'allowance_kind');
        $this->assertNotNull($kindField);
        $this->assertEquals('select', $kindField['type']);
        $this->assertEquals('type_privilege_id', $kindField['source']);
        $this->assertEquals('type_privileges', $kindField['options_source']);
        $this->assertTrue($kindField['required']);
    }

    public function test_health_insurance_allowance_type_has_correct_source(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE);

        $typeField = $this->findFieldByKey($config['fields'], 'allowance_type');
        $this->assertNotNull($typeField);
        $this->assertEquals('select', $typeField['type']);
        $this->assertEquals('type_allowance_code', $typeField['source']);
        $this->assertEquals('type_allowances', $typeField['options_source']);
        $this->assertTrue($typeField['required']);
    }

    public function test_health_insurance_description_is_optional(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE);

        $descField = $this->findFieldByKey($config['fields'], 'description');
        $this->assertNotNull($descField);
        $this->assertFalse($descField['required']);
        $this->assertEquals('textarea', $descField['type']);
    }

    // ---------------------------------------------------------------
    // All privilege types have valid configs
    // ---------------------------------------------------------------

    public function test_all_privilege_types_return_valid_configs(): void
    {
        $types = [
            PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE,
            PrivilegeCardConfigService::TYPE_SOCIAL_INSURANCE,
            PrivilegeCardConfigService::TYPE_HOUSING_ALLOWANCE,
            PrivilegeCardConfigService::TYPE_FLIGHT_RESERVATION,
            PrivilegeCardConfigService::TYPE_CAR_ALLOWANCE,
            PrivilegeCardConfigService::TYPE_TELECOMMUNICATIONS_ALLOWANCE,
        ];

        foreach ($types as $type) {
            $config = $this->service->getCardConfig($type);

            $this->assertArrayHasKey('fields', $config, "Type '$type' is missing 'fields' key.");
            $this->assertNotEmpty($config['fields'], "Type '$type' has empty fields.");

            foreach ($config['fields'] as $field) {
                $this->assertArrayHasKey('key', $field, "Field in '$type' missing 'key'.");
                $this->assertArrayHasKey('label_ar', $field);
                $this->assertArrayHasKey('label_en', $field);
                $this->assertArrayHasKey('type', $field);
                $this->assertArrayHasKey('source', $field);
                $this->assertArrayHasKey('required', $field);
                $this->assertArrayHasKey('options_source', $field);
            }
        }
    }

    public function test_all_configs_have_unique_field_keys(): void
    {
        $allConfigs = $this->service->allConfigs();

        foreach ($allConfigs as $type => $config) {
            $keys = array_column($config['fields'], 'key');
            $uniqueKeys = array_unique($keys);

            $this->assertCount(
                count($keys),
                $uniqueKeys,
                "Type '$type' has duplicate field keys."
            );
        }
    }

    // ---------------------------------------------------------------
    // Unknown type returns generic config
    // ---------------------------------------------------------------

    public function test_unknown_type_returns_generic_config(): void
    {
        $config = $this->service->getCardConfig('nonexistent_type');

        $this->assertArrayHasKey('fields', $config);
        $this->assertNotEmpty($config['fields']);
    }

    public function test_generic_config_has_charge_amount_and_description(): void
    {
        $config = $this->service->getCardConfig('nonexistent_type');

        $keys = array_column($config['fields'], 'key');

        $this->assertContains('charge_amount', $keys);
        $this->assertContains('description', $keys);
    }

    // ---------------------------------------------------------------
    // Existing privilege types are not broken
    // ---------------------------------------------------------------

    public function test_social_insurance_config_exists_and_is_valid(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_SOCIAL_INSURANCE);

        $keys = array_column($config['fields'], 'key');
        $this->assertContains('charge_amount', $keys);
        $this->assertContains('allowance_type', $keys);
        $this->assertContains('description', $keys);
    }

    public function test_housing_allowance_config_exists_and_is_valid(): void
    {
        $config = $this->service->getCardConfig(PrivilegeCardConfigService::TYPE_HOUSING_ALLOWANCE);

        $keys = array_column($config['fields'], 'key');
        $this->assertContains('charge_amount', $keys);
        $this->assertContains('allowance_type', $keys);
        $this->assertContains('description', $keys);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function findFieldByKey(array $fields, string $key): ?array
    {
        foreach ($fields as $field) {
            if ($field['key'] === $key) {
                return $field;
            }
        }

        return null;
    }
}

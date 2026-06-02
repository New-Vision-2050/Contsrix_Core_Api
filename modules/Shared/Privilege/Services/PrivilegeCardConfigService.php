<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Services;

/**
 * Maps privilege types to their card/form field configurations.
 *
 * This service provides a single source of truth for what fields
 * the frontend should render when displaying/editing a privilege card.
 *
 * Adding a new privilege type:
 * 1. Add a new config entry in allConfigs() keyed by the privilege type slug.
 * 2. Each field is { key, label_ar, label_en, type, source, required (bool), options_source (nullable) }.
 * 3. No code changes needed elsewhere — presenters read from here dynamically.
 */
class PrivilegeCardConfigService
{
    /**
     * Supported privilege type slugs.
     */
    public const TYPE_HEALTH_INSURANCE                = 'health_insurance';
    public const TYPE_SOCIAL_INSURANCE                 = 'social_insurance';
    public const TYPE_HOUSING_ALLOWANCE                = 'housing_allowance';
    public const TYPE_FLIGHT_RESERVATION               = 'flight_reservation';
    public const TYPE_CAR_ALLOWANCE                    = 'car_allowance';
    public const TYPE_TELECOMMUNICATIONS_ALLOWANCE     = 'telecommunications_allowance';

    /**
     * Return the card field configuration for a given privilege type.
     *
     * @return array{fields: array<array{key:string, label_ar:string, label_en:string, type:string, source:string, required:bool, options_source:string|null}>}
     */
    public function getCardConfig(string $privilegeType): array
    {
        $configs = $this->allConfigs();

        return $configs[$privilegeType] ?? $this->genericConfig();
    }

    /**
     * Return configs for all known privilege types.
     */
    public function allConfigs(): array
    {
        return [
            self::TYPE_HEALTH_INSURANCE                => $this->healthInsuranceConfig(),
            self::TYPE_SOCIAL_INSURANCE                 => $this->socialInsuranceConfig(),
            self::TYPE_HOUSING_ALLOWANCE                => $this->housingAllowanceConfig(),
            self::TYPE_FLIGHT_RESERVATION               => $this->flightReservationConfig(),
            self::TYPE_CAR_ALLOWANCE                    => $this->carAllowanceConfig(),
            self::TYPE_TELECOMMUNICATIONS_ALLOWANCE     => $this->telecommunicationsAllowanceConfig(),
        ];
    }

    // ---------------------------------------------------------------
    // Health Insurance / تأمين طبي
    // ---------------------------------------------------------------

    private function healthInsuranceConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'policy_number',
                    'label_ar'       => 'رقم البوليصة',
                    'label_en'       => 'Policy Number',
                    'type'           => 'text',
                    'source'         => 'medical_insurance.policy_number',
                    'required'       => true,
                    'options_source' => null,
                ],
                [
                    'key'            => 'allowance_kind',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Kind',
                    'type'           => 'select',
                    'source'         => 'type_privilege_id',
                    'required'       => true,
                    'options_source' => 'type_privileges',
                ],
                [
                    'key'            => 'allowance_type',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Type',
                    'type'           => 'select',
                    'source'         => 'type_allowance_code',
                    'required'       => true,
                    'options_source' => 'type_allowances',
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Social Insurance / تأمين اجتماعي
    // ---------------------------------------------------------------

    private function socialInsuranceConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'charge_amount',
                    'label_ar'       => 'المبلغ',
                    'label_en'       => 'Amount',
                    'type'           => 'number',
                    'source'         => 'charge_amount',
                    'required'       => true,
                    'options_source' => null,
                ],
                [
                    'key'            => 'allowance_type',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Type',
                    'type'           => 'select',
                    'source'         => 'type_allowance_code',
                    'required'       => true,
                    'options_source' => 'type_allowances',
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Housing Allowance / بدل سكن
    // ---------------------------------------------------------------

    private function housingAllowanceConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'charge_amount',
                    'label_ar'       => 'المبلغ',
                    'label_en'       => 'Amount',
                    'type'           => 'number',
                    'source'         => 'charge_amount',
                    'required'       => true,
                    'options_source' => null,
                ],
                [
                    'key'            => 'allowance_type',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Type',
                    'type'           => 'select',
                    'source'         => 'type_allowance_code',
                    'required'       => true,
                    'options_source' => 'type_allowances',
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Flight Reservation / حجز طيران
    // ---------------------------------------------------------------

    private function flightReservationConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'charge_amount',
                    'label_ar'       => 'المبلغ',
                    'label_en'       => 'Amount',
                    'type'           => 'number',
                    'source'         => 'charge_amount',
                    'required'       => true,
                    'options_source' => null,
                ],
                [
                    'key'            => 'allowance_type',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Type',
                    'type'           => 'select',
                    'source'         => 'type_allowance_code',
                    'required'       => true,
                    'options_source' => 'type_allowances',
                ],
                [
                    'key'            => 'allowance_kind',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Kind',
                    'type'           => 'select',
                    'source'         => 'type_privilege_id',
                    'required'       => false,
                    'options_source' => 'type_privileges',
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Car Allowance / بدل سيارة
    // ---------------------------------------------------------------

    private function carAllowanceConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'charge_amount',
                    'label_ar'       => 'المبلغ',
                    'label_en'       => 'Amount',
                    'type'           => 'number',
                    'source'         => 'charge_amount',
                    'required'       => true,
                    'options_source' => null,
                ],
                [
                    'key'            => 'allowance_type',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Type',
                    'type'           => 'select',
                    'source'         => 'type_allowance_code',
                    'required'       => true,
                    'options_source' => 'type_allowances',
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Telecommunications Allowance / بدل اتصالات
    // ---------------------------------------------------------------

    private function telecommunicationsAllowanceConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'charge_amount',
                    'label_ar'       => 'المبلغ',
                    'label_en'       => 'Amount',
                    'type'           => 'number',
                    'source'         => 'charge_amount',
                    'required'       => true,
                    'options_source' => null,
                ],
                [
                    'key'            => 'allowance_type',
                    'label_ar'       => 'نوع البدل',
                    'label_en'       => 'Allowance Type',
                    'type'           => 'select',
                    'source'         => 'type_allowance_code',
                    'required'       => true,
                    'options_source' => 'type_allowances',
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Generic / fallback
    // ---------------------------------------------------------------

    private function genericConfig(): array
    {
        return [
            'fields' => [
                [
                    'key'            => 'charge_amount',
                    'label_ar'       => 'المبلغ',
                    'label_en'       => 'Amount',
                    'type'           => 'number',
                    'source'         => 'charge_amount',
                    'required'       => false,
                    'options_source' => null,
                ],
                [
                    'key'            => 'description',
                    'label_ar'       => 'وصف',
                    'label_en'       => 'Description',
                    'type'           => 'textarea',
                    'source'         => 'description',
                    'required'       => false,
                    'options_source' => null,
                ],
            ],
        ];
    }
}

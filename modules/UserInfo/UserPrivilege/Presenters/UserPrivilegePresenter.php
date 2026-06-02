<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Presenters;

use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Period\Presenters\PeriodPresenter;
use Modules\Shared\Privilege\Presenters\PrivilegePresenter;
use Modules\Shared\TypeAllowance\Presenters\TypeAllowancePresenter;
use Modules\Shared\TypePrivilege\Presenters\TypePrivilegePresenter;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;

class UserPrivilegePresenter extends AbstractPresenter
{
    private UserPrivilege $userPrivilege;
    private PrivilegeCardConfigService $cardConfigService;

    public function __construct(UserPrivilege $userPrivilege)
    {
        $this->userPrivilege = $userPrivilege;
        $this->cardConfigService = app(PrivilegeCardConfigService::class);
    }

    protected function present(bool $isListing = false): array
    {
        $privilegeType = $this->userPrivilege->privilege?->type;

        $data = [
            'id' => $this->userPrivilege->id,
            'type_privilege_id' => $this->userPrivilege->type_privilege_id,
            'type_allowance_code' => $this->userPrivilege->type_allowance_code,
            'period_id' => $this->userPrivilege->period_id,
            'medical_insurance_id' => $this->userPrivilege->medical_insurance_id,
            'type_privilege' => $this->userPrivilege->typePrivilege
                ? (new TypePrivilegePresenter($this->userPrivilege->typePrivilege))->getData()
                : null,
            'type_allowance' => $this->userPrivilege->typeAllowance
                ? (new TypeAllowancePresenter($this->userPrivilege->typeAllowance))->getData()
                : null,
            'charge_amount' => $this->userPrivilege->charge_amount,
            'description' => $this->userPrivilege->description,
            'period' => $this->userPrivilege->period
                ? (new PeriodPresenter($this->userPrivilege->period))->getData()
                : null,
            'privilege' => $this->userPrivilege->privilege
                ? (new PrivilegePresenter($this->userPrivilege->privilege))->getData()
                : null,
            'medical_insurance' => $this->presentMedicalInsurance(),
        ];

        // Include card field configuration so the frontend knows which fields to render.
        if ($privilegeType !== null) {
            $data['card_fields'] = $this->cardConfigService->getCardConfig($privilegeType);
        }

        return $data;
    }

    /**
     * Present medical insurance card data with all relevant fields.
     */
    private function presentMedicalInsurance(): ?array
    {
        if (! $this->userPrivilege->medicalInsurance) {
            return null;
        }

        $insurance = $this->userPrivilege->medicalInsurance;

        return [
            'id'                => $insurance->id,
            'name'              => $insurance->name,
            'policy_number'     => $insurance->policy_number,
            'provider'          => $insurance->provider,
            'start_date'        => $insurance->start_date?->format('Y-m-d'),
            'end_date'          => $insurance->end_date?->format('Y-m-d'),
            'value'             => $insurance->value,
            'individuals_count' => $insurance->individuals_count,
            'status'            => $insurance->status,
        ];
    }
}

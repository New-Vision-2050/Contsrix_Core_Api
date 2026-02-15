<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class ChartsPresenter extends AbstractPresenter
{
    private array $genderData;
    private array $ageData;
    private array $jobTypeData;
    private array $visaExpirationData;
    private array $visaStatusData;
    private array $contractExpirationData;
    private array $contractStatusData;
    private array $nationalityData;
    private array $maritalStatusData;

    public function __construct(
        array $genderData,
        array $ageData,
        array $jobTypeData,
        array $visaExpirationData,
        array $visaStatusData,
        array $contractExpirationData,
        array $contractStatusData,
        array $nationalityData,
        array $maritalStatusData
    ) {
        $this->genderData = $genderData;
        $this->ageData = $ageData;
        $this->jobTypeData = $jobTypeData;
        $this->visaExpirationData = $visaExpirationData;
        $this->visaStatusData = $visaStatusData;
        $this->contractExpirationData = $contractExpirationData;
        $this->contractStatusData = $contractStatusData;
        $this->nationalityData = $nationalityData;
        $this->maritalStatusData = $maritalStatusData;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'gender' => $this->presentGender(),
            'age' => $this->presentAge(),
            'job_type' => $this->presentJobType(),
            'visa_expiration_by_month' => $this->presentVisaExpirationByMonth(),
            'visa_status' => $this->presentVisaStatus(),
            'contract_expiration_by_month' => $this->presentContractExpirationByMonth(),
            'contract_status' => $this->presentContractStatus(),
            'nationality' => $this->presentNationality(),
            'marital_status' => $this->presentMaritalStatus(),
        ];
    }

    private function presentGender(): array
    {
        return [
            'chart_type' => 'gender',
            'total' => $this->genderData['total'],
            'data' => [
                [
                    'label' => __('ذكر'),
                    'code' => 'male',
                    'count' => $this->genderData['male']['count'],
                    'percentage' => $this->genderData['male']['percentage'],
                ],
                [
                    'label' => __('انثي'),
                    'code' => 'female',
                    'count' => $this->genderData['female']['count'],
                    'percentage' => $this->genderData['female']['percentage'],
                ],
                [
                    'label' => __('غير محدد'),
                    'code' => 'unspecified',
                    'count' => $this->genderData['unspecified']['count'],
                    'percentage' => $this->genderData['unspecified']['percentage'],
                ],
            ],
        ];
    }

    private function presentAge(): array
    {
        $ageRanges = [];
        foreach ($this->ageData['ranges'] as $range => $data) {
            $ageRanges[] = [
                'label' => $range === 'unspecified' ? __('غير محدد') : $range,
                'code' => $range,
                'count' => $data['count'],
                'percentage' => $data['percentage'],
            ];
        }

        return [
            'chart_type' => 'age',
            'total' => $this->ageData['total'],
            'data' => $ageRanges,
        ];
    }

    private function presentJobType(): array
    {
        $jobTypes = [];
        foreach ($this->jobTypeData['data'] as $item) {
            $entry = [
                'job_type_id' => $item['job_type_id'],
                'label' => $item['name'],
                'count' => $item['count'],
                'percentage' => $item['percentage'],
            ];
            if (isset($item['code'])) {
                $entry['code'] = $item['code'];
            }
            $jobTypes[] = $entry;
        }

        return [
            'chart_type' => 'job_type',
            'total' => $this->jobTypeData['total'],
            'data' => $jobTypes,
        ];
    }

    private function presentVisaExpirationByMonth(): array
    {
        return [
            'chart_type' => 'visa_expiration_by_month',
            'total' => $this->visaExpirationData['total'],
            'data' => $this->visaExpirationData['data'],
        ];
    }

    private function presentVisaStatus(): array
    {
        return [
            'chart_type' => 'visa_status',
            'total' => $this->visaStatusData['total'],
            'data' => [
                [
                    'label' => __('منتهية'),
                    'code' => 'expired',
                    'count' => $this->visaStatusData['expired']['count'],
                    'percentage' => $this->visaStatusData['expired']['percentage'],
                ],
                [
                    'label' => __('سارية'),
                    'code' => 'valid',
                    'count' => $this->visaStatusData['valid']['count'],
                    'percentage' => $this->visaStatusData['valid']['percentage'],
                ],
                [
                    'label' => __('بدون تأشيرة'),
                    'code' => 'no_visa',
                    'count' => $this->visaStatusData['no_visa']['count'],
                    'percentage' => $this->visaStatusData['no_visa']['percentage'],
                ],
            ],
        ];
    }

    private function presentContractExpirationByMonth(): array
    {
        return [
            'chart_type' => 'contract_expiration_by_month',
            'total' => $this->contractExpirationData['total'],
            'data' => $this->contractExpirationData['data'],
        ];
    }

    private function presentContractStatus(): array
    {
        return [
            'chart_type' => 'contract_status',
            'total' => $this->contractStatusData['total'],
            'data' => [
                [
                    'label' => __('منتهي'),
                    'code' => 'expired',
                    'count' => $this->contractStatusData['expired']['count'],
                    'percentage' => $this->contractStatusData['expired']['percentage'],
                ],
                [
                    'label' => __('ساري'),
                    'code' => 'valid',
                    'count' => $this->contractStatusData['valid']['count'],
                    'percentage' => $this->contractStatusData['valid']['percentage'],
                ],
                [
                    'label' => __('بدون عقد'),
                    'code' => 'no_contract',
                    'count' => $this->contractStatusData['no_contract']['count'],
                    'percentage' => $this->contractStatusData['no_contract']['percentage'],
                ],
            ],
        ];
    }

    private function presentNationality(): array
    {
        $nationalities = [];
        foreach ($this->nationalityData['data'] as $item) {
            $entry = [
                'country_id' => $item['country_id'],
                'label' => $item['name'] ?? $item['code'] ?? null,
                'count' => $item['count'],
                'percentage' => $item['percentage'],
            ];
            if (isset($item['code'])) {
                $entry['code'] = $item['code'];
            }
            $nationalities[] = $entry;
        }

        return [
            'chart_type' => 'nationality',
            'total' => $this->nationalityData['total'],
            'data' => $nationalities,
        ];
    }

    private function presentMaritalStatus(): array
    {
        $statuses = [];
        foreach ($this->maritalStatusData['data'] as $item) {
            $statuses[] = [
                'marital_status_id' => $item['marital_status_id'],
                'label' => $item['name'],
                'count' => $item['count'],
                'percentage' => $item['percentage'],
            ];
        }

        return [
            'chart_type' => 'marital_status',
            'total' => $this->maritalStatusData['total'],
            'data' => $statuses,
        ];
    }
}

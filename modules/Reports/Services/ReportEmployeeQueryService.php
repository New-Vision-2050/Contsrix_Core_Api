<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Reports\DTO\ReportWizardStep2DTO;
use Modules\Reports\Enums\ReportEnums;

/**
 * Builds an Eloquent query over CompanyUser (+ joined UserProfessionalData)
 * for the report recipient set described by Step 2 of the wizard.
 *
 * Centralising this lets both the generation job and the "preview count"
 * endpoint (future) share the exact same filter logic.
 */
class ReportEmployeeQueryService
{
    /**
     * Build the query for the current tenant. Returns CompanyUser rows that
     * match every Step 2 filter and are tied to a user in the tenant company.
     */
    public function query(ReportWizardStep2DTO $step2): Builder
    {
        $query = CompanyUser::query()
            ->with(['users', 'userProfessionalData', 'country', 'jobTitle'])
            ->whereHas('users', fn ($q) => $q->where('company_id', tenant('id')));

        $this->applyStatus($query, $step2);
        $this->applyHierarchy($query, $step2);
        $this->applyJobTitle($query, $step2);
        $this->applyContractTypes($query, $step2);
        $this->applyNationality($query, $step2);
        $this->applyGender($query, $step2);

        return $query;
    }

    private function applyStatus(Builder $query, ReportWizardStep2DTO $s): void
    {
        if ($s->employeeStatus === ReportEnums::EMPLOYEE_STATUS_ALL) {
            return;
        }

        // Map logical status to CompanyUser.active_type (the field used by
        // CompanyUserDatatatusService). "on_leave" and "dismissed" are
        // delegated to the users table flags when applicable.
        $query->whereHas('users', function ($q) use ($s) {
            match ($s->employeeStatus) {
                ReportEnums::EMPLOYEE_STATUS_ACTIVE   => $q->where('status', 1),
                ReportEnums::EMPLOYEE_STATUS_INACTIVE => $q->where('status', 0),
                // on_leave / dismissed rely on domain-specific flags — callers
                // can layer those on when the HR module is wired up.
                default => null,
            };
        });
    }

    private function applyHierarchy(Builder $query, ReportWizardStep2DTO $s): void
    {
        if ($s->location === null && $s->management === null && $s->department === null) {
            return;
        }

        $query->whereHas('userProfessionalData', function ($q) use ($s) {
            if ($s->location !== null && $s->location !== '' && $s->location !== 'all') {
                $q->where('branch_id', $s->location);
            }
            if ($s->management !== null && $s->management !== '' && $s->management !== 'all') {
                $q->where('management_id', $s->management);
            }
            if ($s->department !== null && $s->department !== '' && $s->department !== 'all') {
                $q->where('department_id', $s->department);
            }
        });
    }

    private function applyJobTitle(Builder $query, ReportWizardStep2DTO $s): void
    {
        if ($s->jobTitle === null || $s->jobTitle === '' || $s->jobTitle === 'all') {
            return;
        }

        $query->where(function ($q) use ($s) {
            $q->where('job_title_id', $s->jobTitle)
                ->orWhereHas('userProfessionalData', fn ($q2) => $q2->where('job_title_id', $s->jobTitle));
        });
    }

    private function applyContractTypes(Builder $query, ReportWizardStep2DTO $s): void
    {
        if ($s->contractTypeIds === []) {
            return;
        }

        // Contract-type slugs are string keys that map to `job_types.type`
        // (e.g. "full_time"). The schema doesn't yet enforce this mapping, so
        // callers that store contract types on a dedicated column can layer
        // their own rules on top of the query.
        $query->whereHas('userProfessionalData.jobType', function ($q) use ($s) {
            $q->whereIn('type', $s->contractTypeIds);
        });
    }

    private function applyNationality(Builder $query, ReportWizardStep2DTO $s): void
    {
        if ($s->nationality === null || $s->nationality === '' || $s->nationality === 'all') {
            return;
        }

        // Accept either a country_id (uuid/int), nationality name, or ISO code string.
        $query->where(function ($q) use ($s) {
            $q->where('country_id', $s->nationality)
                ->orWhereHas('country', fn ($c) => $c->where('nationality', $s->nationality))
                ->orWhereHas('country', fn ($c) => $c->where('iso2', $s->nationality));
        });
    }

    private function applyGender(Builder $query, ReportWizardStep2DTO $s): void
    {
        if ($s->gender === null || $s->gender === '' || $s->gender === 'all') {
            return;
        }

        $query->where('gender', $s->gender);
    }
}

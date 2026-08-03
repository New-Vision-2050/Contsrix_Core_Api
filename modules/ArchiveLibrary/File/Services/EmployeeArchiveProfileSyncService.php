<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\JobOffer\Models\JobOffer;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;
use Modules\UserInfo\Qualification\Models\Qualification;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;
use Throwable;

class EmployeeArchiveProfileSyncService
{
    private const COMPANY_USER_COLLECTIONS = [
        'upload_user' => [
            EmployeeArchiveFileService::SECTION_PERSONAL,
            EmployeeArchiveFileService::SUB_PERSONAL_PHOTO,
        ],
        'file_passport' => [
            EmployeeArchiveFileService::SECTION_PERSONAL,
            EmployeeArchiveFileService::SUB_RESIDENCE_INFO,
        ],
        'file_identity' => [
            EmployeeArchiveFileService::SECTION_PERSONAL,
            EmployeeArchiveFileService::SUB_RESIDENCE_INFO,
        ],
        'file_border_number' => [
            EmployeeArchiveFileService::SECTION_PERSONAL,
            EmployeeArchiveFileService::SUB_RESIDENCE_INFO,
        ],
        'file_entry_number' => [
            EmployeeArchiveFileService::SECTION_PERSONAL,
            EmployeeArchiveFileService::SUB_RESIDENCE_INFO,
        ],
        'file_work_permit' => [
            EmployeeArchiveFileService::SECTION_PERSONAL,
            EmployeeArchiveFileService::SUB_RESIDENCE_INFO,
        ],
        'upload_biography' => [
            EmployeeArchiveFileService::SECTION_ACADEMIC,
            EmployeeArchiveFileService::SUB_CV,
        ],
    ];

    /**
     * @var array<class-string<Model>, array{collection: string, section: string, subsection: string}>
     */
    private const RELATED_MODEL_COLLECTIONS = [
        Qualification::class => [
            'collection' => 'upload_Qualification',
            'section' => EmployeeArchiveFileService::SECTION_ACADEMIC,
            'subsection' => EmployeeArchiveFileService::SUB_QUALIFICATION,
        ],
        UserEducationalCourse::class => [
            'collection' => 'upload',
            'section' => EmployeeArchiveFileService::SECTION_ACADEMIC,
            'subsection' => EmployeeArchiveFileService::SUB_COURSES,
        ],
        ProfessionalCertificate::class => [
            'collection' => 'upload',
            'section' => EmployeeArchiveFileService::SECTION_ACADEMIC,
            'subsection' => EmployeeArchiveFileService::SUB_PROFESSIONAL_CERTIFICATES,
        ],
        JobOffer::class => [
            'collection' => 'upload_offerjob',
            'section' => EmployeeArchiveFileService::SECTION_EMPLOYMENT,
            'subsection' => EmployeeArchiveFileService::SUB_JOB_OFFER,
        ],
        EmploymentContract::class => [
            'collection' => 'upload_employment_contracts',
            'section' => EmployeeArchiveFileService::SECTION_EMPLOYMENT,
            'subsection' => EmployeeArchiveFileService::SUB_EMPLOYMENT_CONTRACT,
        ],
    ];

    public function __construct(
        private EmployeeArchiveFileService $employeeArchiveFileService,
    ) {}

    /**
     * @return array{
     *     companies: int,
     *     employees: int,
     *     media_checked: int,
     *     created: int,
     *     updated: int,
     *     attached: int,
     *     unchanged: int,
     *     skipped: int,
     *     errors: array<int, array{company_id: string|null, employee_global_id: string|null, message: string}>
     * }
     */
    public function sync(
        ?string $companyId = null,
        ?string $employeeGlobalId = null,
        bool $dryRun = false,
        ?array $employeeGlobalIds = null,
    ): array {
        $summary = $this->emptySummary();
        $employeeGlobalIds = $this->normalizeEmployeeGlobalIds($employeeGlobalId, $employeeGlobalIds);

        $companies = Company::query()
            ->when($companyId, fn ($query) => $query->where('id', $companyId))
            ->orderBy('id')
            ->get();

        $previousTenant = tenancy()->initialized ? tenant() : null;

        try {
            foreach ($companies as $company) {
                tenancy()->end();
                tenancy()->initialize($company);

                $this->mergeSummary(
                    $summary,
                    $this->syncCurrentCompany($company, $employeeGlobalIds, $dryRun)
                );
            }
        } finally {
            tenancy()->end();

            if ($previousTenant) {
                tenancy()->initialize($previousTenant);
            }
        }

        return $summary;
    }

    /**
     * @return array{
     *     companies: int,
     *     employees: int,
     *     media_checked: int,
     *     created: int,
     *     updated: int,
     *     attached: int,
     *     unchanged: int,
     *     skipped: int,
     *     errors: array<int, array{company_id: string|null, employee_global_id: string|null, message: string}>
     * }
     */
    private function syncCurrentCompany(Company $company, ?array $employeeGlobalIds, bool $dryRun): array
    {
        $summary = $this->emptySummary();
        $summary['companies'] = 1;

        User::query()
            ->withoutTenancy()
            ->where('company_id', $company->id)
            ->whereNotNull('global_company_user_id')
            ->when($employeeGlobalIds !== null, fn ($query) => $query->whereIn('global_company_user_id', $employeeGlobalIds))
            ->whereHas('companyUserCompanies', function ($query) use ($company) {
                $query->where('company_users_companies.company_id', $company->id)
                    ->where('company_users_companies.role', CompanyUserRole::EMPLOYEE->value)
                    ->where('company_users_companies.status', 1);
            })
            ->with('companyUser')
            ->orderBy('id')
            ->chunk(100, function ($users) use ($company, $dryRun, &$summary) {
                foreach ($users as $user) {
                    $employeeGlobalId = (string) $user->global_company_user_id;

                    try {
                        $companyUser = $user->companyUser
                            ?: CompanyUser::query()->withoutParentModel()->where('global_id', $employeeGlobalId)->first();

                        if (! $companyUser) {
                            $summary['employees']++;
                            $summary['skipped']++;

                            continue;
                        }

                        $this->mergeSummary(
                            $summary,
                            $this->syncEmployee($company, $user, $companyUser, $dryRun)
                        );
                    } catch (Throwable $exception) {
                        $summary['errors'][] = [
                            'company_id' => (string) $company->id,
                            'employee_global_id' => $employeeGlobalId,
                            'message' => $exception->getMessage(),
                        ];
                    }
                }
            });

        return $summary;
    }

    /**
     * @return array{
     *     companies: int,
     *     employees: int,
     *     media_checked: int,
     *     created: int,
     *     updated: int,
     *     attached: int,
     *     unchanged: int,
     *     skipped: int,
     *     errors: array<int, array{company_id: string|null, employee_global_id: string|null, message: string}>
     * }
     */
    private function syncEmployee(Company $company, User $user, CompanyUser $companyUser, bool $dryRun): array
    {
        $summary = $this->emptySummary();
        $summary['employees'] = 1;

        $companyId = (string) $company->id;
        $employeeGlobalId = (string) $user->global_company_user_id;
        $employeeName = (string) ($user->name ?: $companyUser->name ?: $employeeGlobalId);

        foreach (self::COMPANY_USER_COLLECTIONS as $collection => [$section, $subsection]) {
            $this->syncCollection(
                summary: $summary,
                companyId: $companyId,
                employeeGlobalId: $employeeGlobalId,
                employeeName: $employeeName,
                sourceModel: $companyUser,
                collection: $collection,
                section: $section,
                subsection: $subsection,
                dryRun: $dryRun,
            );
        }

        foreach (self::RELATED_MODEL_COLLECTIONS as $modelClass => $config) {
            $modelClass::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('global_id', $employeeGlobalId)
                ->orderBy('id')
                ->get()
                ->each(function (Model $sourceModel) use (&$summary, $companyId, $employeeGlobalId, $employeeName, $config, $dryRun) {
                    $this->syncCollection(
                        summary: $summary,
                        companyId: $companyId,
                        employeeGlobalId: $employeeGlobalId,
                        employeeName: $employeeName,
                        sourceModel: $sourceModel,
                        collection: $config['collection'],
                        section: $config['section'],
                        subsection: $config['subsection'],
                        dryRun: $dryRun,
                    );
                });
        }

        return $summary;
    }

    private function syncCollection(
        array &$summary,
        string $companyId,
        string $employeeGlobalId,
        string $employeeName,
        Model $sourceModel,
        string $collection,
        string $section,
        string $subsection,
        bool $dryRun,
    ): void {
        $mediaItems = $sourceModel->getMedia($collection);

        if ($mediaItems->isEmpty()) {
            return;
        }

        foreach ($this->employeeArchiveFileService->syncExistingMedia(
            companyId: $companyId,
            employeeGlobalId: $employeeGlobalId,
            employeeName: $employeeName,
            sourceMedia: $mediaItems,
            mainSection: $section,
            subSection: $subsection,
            sourceModel: $sourceModel,
            dryRun: $dryRun,
        ) as $result) {
            $this->applyResult($summary, $result);
        }
    }

    private function applyResult(array &$summary, array $result): void
    {
        $summary['media_checked']++;

        if ($result['skipped']) {
            $summary['skipped']++;

            return;
        }

        $changed = false;

        foreach (['created', 'updated', 'attached'] as $key) {
            if ($result[$key]) {
                $summary[$key]++;
                $changed = true;
            }
        }

        if (! $changed) {
            $summary['unchanged']++;
        }
    }

    /**
     * @return array{
     *     companies: int,
     *     employees: int,
     *     media_checked: int,
     *     created: int,
     *     updated: int,
     *     attached: int,
     *     unchanged: int,
     *     skipped: int,
     *     errors: array<int, array{company_id: string|null, employee_global_id: string|null, message: string}>
     * }
     */
    private function emptySummary(): array
    {
        return [
            'companies' => 0,
            'employees' => 0,
            'media_checked' => 0,
            'created' => 0,
            'updated' => 0,
            'attached' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
    }

    private function mergeSummary(array &$target, array $source): void
    {
        foreach (['companies', 'employees', 'media_checked', 'created', 'updated', 'attached', 'unchanged', 'skipped'] as $key) {
            $target[$key] += $source[$key];
        }

        $target['errors'] = array_merge($target['errors'], $source['errors']);
    }

    private function normalizeEmployeeGlobalIds(?string $employeeGlobalId, ?array $employeeGlobalIds): ?array
    {
        $ids = $employeeGlobalIds ?? [];

        if ($employeeGlobalId !== null && $employeeGlobalId !== '') {
            $ids[] = $employeeGlobalId;
        }

        $ids = array_values(array_unique(array_filter(
            array_map(fn ($id) => is_string($id) ? trim($id) : null, $ids)
        )));

        return $ids === [] ? null : $ids;
    }
}

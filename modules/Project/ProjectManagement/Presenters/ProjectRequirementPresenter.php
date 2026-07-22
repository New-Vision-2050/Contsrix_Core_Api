<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyCore\Models\Company;
use Modules\DocumentType\Models\DocumentType;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;

class ProjectRequirementPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectRequirement $requirement) {}

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->requirement->id,
            'company_id' => $this->requirement->company_id,
            'project_id' => $this->requirement->project_id,
            'requirement_code' => $this->requirement->requirement_code,
            'required_document_name' => $this->requirement->required_document_name,
            'document' => $this->requirement->document,
            'document_type_id' => $this->requirement->document_type_id,
            'document_type' => $this->requirement->document_type,
            'document_type_lookup' => $this->documentTypeData($this->requirement->documentType),
            'specialization_id' => $this->requirement->specialization_id,
            'specialization' => $this->requirement->specialization,
            'specialization_lookup' => $this->specializationData($this->requirement->specializationLookup),
            'stage' => $this->requirement->stage,
            'sending_entity_id' => $this->requirement->sending_entity_id,
            'sending_entity' => $this->requirement->sending_entity,
            'sending_entity_company' => $this->companyData($this->requirement->sendingEntityCompany),
            'review_entity_id' => $this->requirement->review_entity_id,
            'review_entity' => $this->requirement->review_entity,
            'review_entity_company' => $this->companyData($this->requirement->reviewEntityCompany),
            'receiver_company_ids' => $this->requirement->receiverCompanies
                ->pluck('id')
                ->values()
                ->all(),
            'receiver_companies' => $this->requirement->receiverCompanies
                ->map(fn (Company $company): array => $this->companyData($company) ?? [])
                ->values()
                ->all(),
            'repetition' => $this->requirement->repetition,
            'repetition_interval_type' => $this->requirement->repetition_interval_type,
            'repeat_days' => $this->requirement->repeat_days,
            'evaluation_status' => $this->requirement->evaluation_status,
            'resulting_document' => $this->requirement->resulting_document,
            'completion_percentage' => $this->requirement->completion_percentage,
            'created_at' => $this->requirement->created_at?->toDateTimeString(),
            'updated_at' => $this->requirement->updated_at?->toDateTimeString(),
        ];
    }

    private function documentTypeData(?DocumentType $documentType): ?array
    {
        if (! $documentType) {
            return null;
        }

        return [
            'id' => $documentType->id,
            'name' => $documentType->name,
            'is_active' => (bool) $documentType->is_active,
        ];
    }

    private function specializationData(?AcademicSpecialization $specialization): ?array
    {
        if (! $specialization) {
            return null;
        }

        return [
            'id' => $specialization->id,
            'name' => $specialization->name,
            'code' => $specialization->code,
            'academic_qualification_id' => $specialization->academic_qualification_id,
        ];
    }

    private function companyData(?Company $company): ?array
    {
        if (! $company) {
            return null;
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
            'serial_no' => $company->serial_no,
            'serial_number' => $company->serial_no,
            'email' => $company->email,
            'phone' => $company->phone,
        ];
    }
}

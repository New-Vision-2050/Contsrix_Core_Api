<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectManagement\Models\ProjectContractor;

class ProjectContractorPresenter extends AbstractPresenter
{
    private ProjectContractor $projectContractor;

    public function __construct(ProjectContractor $projectContractor)
    {
        $this->projectContractor = $projectContractor;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->projectContractor->id,
            'project_id' => $this->projectContractor->project_id,
            'name' => $this->projectContractor->name,
            'tax_card' => $this->projectContractor->tax_card,
            'commercial_register' => $this->projectContractor->commercial_register,
            'activity' => $this->projectContractor->activity,
            'email' => $this->projectContractor->email,
            'country_id' => $this->projectContractor->country_id,
            'country' => $this->projectContractor->relationLoaded('country') && $this->projectContractor->country ? [
                'id' => $this->projectContractor->country->id,
                'name' => $this->projectContractor->country->name,
            ] : null,
            'logo' => $this->projectContractor->getFirstMediaUrl('logo'),
            'project_contractor_id' => $this->projectContractor->project_contractor_id,
            'project_manager_name' => $this->projectContractor->project_manager_name,
            'project_manager_phone' => $this->projectContractor->project_manager_phone,
            'project_manager_nationality' => $this->projectContractor->project_manager_nationality,
            'project_manager_email' => $this->projectContractor->project_manager_email,
            'representatives' => $this->projectContractor->relationLoaded('representatives') ? $this->projectContractor->representatives->map(fn ($representative) => [
                'id' => $representative->id,
                'name' => $representative->name,
                'mobile' => $representative->mobile,
                'nationality' => $representative->nationality,
            ])->values()->all() : [],
            'created_at' => $this->projectContractor->created_at?->toDateTimeString(),
            'updated_at' => $this->projectContractor->updated_at?->toDateTimeString(),
        ];
    }
}

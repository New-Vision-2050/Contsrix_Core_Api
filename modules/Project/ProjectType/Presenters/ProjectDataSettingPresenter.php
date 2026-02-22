<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\ProjectDataSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProjectDataSettingPresenter extends AbstractPresenter
{
    private ProjectDataSetting $setting;

    public function __construct(ProjectDataSetting $setting)
    {
        $this->setting = $setting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->setting->id,
            'project_type_id' => $this->setting->project_type_id,
            'is_reference_number' => $this->setting->is_reference_number,
            'is_name_project' => $this->setting->is_name_project,
            'is_client' => $this->setting->is_client,
            'is_responsible_engineer' => $this->setting->is_responsible_engineer,
            'is_number_contract' => $this->setting->is_number_contract,
            'is_central_cost' => $this->setting->is_central_cost,
            'is_project_value' => $this->setting->is_project_value,
            'is_start_date' => $this->setting->is_start_date,
            'is_achievement_percentage' => $this->setting->is_achievement_percentage,
            'created_at' => $this->setting->created_at?->toDateTimeString(),
            'updated_at' => $this->setting->updated_at?->toDateTimeString(),
        ];
    }
}

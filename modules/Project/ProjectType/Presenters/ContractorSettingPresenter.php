<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\ContractorSetting;

class ContractorSettingPresenter extends AbstractPresenter
{
    private ContractorSetting $setting;

    public function __construct(ContractorSetting $setting)
    {
        $this->setting = $setting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->setting->id,
            'project_type_id' => $this->setting->project_type_id,
            'is_shown' => $this->setting->is_shown,
            'created_at' => $this->setting->created_at?->toDateTimeString(),
            'updated_at' => $this->setting->updated_at?->toDateTimeString(),
        ];
    }
}

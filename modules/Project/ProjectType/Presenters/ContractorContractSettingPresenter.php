<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\ContractorContractSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ContractorContractSettingPresenter extends AbstractPresenter
{
    private ContractorContractSetting $setting;

    public function __construct(ContractorContractSetting $setting)
    {
        $this->setting = $setting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->setting->id,
            'project_type_id' => $this->setting->project_type_id,
            'is_all_data_visible' => $this->setting->is_all_data_visible,
            'created_at' => $this->setting->created_at?->toDateTimeString(),
            'updated_at' => $this->setting->updated_at?->toDateTimeString(),
        ];
    }
}

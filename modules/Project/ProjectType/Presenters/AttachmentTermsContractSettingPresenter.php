<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\AttachmentTermsContractSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AttachmentTermsContractSettingPresenter extends AbstractPresenter
{
    private AttachmentTermsContractSetting $setting;

    public function __construct(AttachmentTermsContractSetting $setting)
    {
        $this->setting = $setting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->setting->id,
            'project_type_id' => $this->setting->project_type_id,
            'is_name' => $this->setting->is_name,
            'is_type' => $this->setting->is_type,
            'is_size' => $this->setting->is_size,
            'is_creator' => $this->setting->is_creator,
            'is_create_date' => $this->setting->is_create_date,
            'is_downloadable' => $this->setting->is_downloadable,
            'created_at' => $this->setting->created_at?->toDateTimeString(),
            'updated_at' => $this->setting->updated_at?->toDateTimeString(),
        ];
    }
}

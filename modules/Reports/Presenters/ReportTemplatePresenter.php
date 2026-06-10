<?php

declare(strict_types=1);

namespace Modules\Reports\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Reports\Models\ReportTemplate;

class ReportTemplatePresenter extends AbstractPresenter
{
    public function __construct(private ReportTemplate $template)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'              => $this->template->id,
            'company_id'      => $this->template->company_id,
            'created_by'      => $this->template->created_by,
            'name'            => $this->template->name,
            'name_ar'         => $this->template->getTranslation('name', 'ar'),
            'name_en'         => $this->template->getTranslation('name', 'en'),
            'description'     => $this->template->description,
            'description_ar'  => $this->template->getTranslation('description', 'ar'),
            'description_en'  => $this->template->getTranslation('description', 'en'),
            'config'          => $this->template->config,
            'is_active'       => (bool) $this->template->is_active,
            'created_at'      => optional($this->template->created_at)->format('Y-m-d h:i:s A'),
            'updated_at'      => optional($this->template->updated_at)->format('Y-m-d h:i:s A'),
        ];
    }
}

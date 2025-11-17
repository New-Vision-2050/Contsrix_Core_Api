<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Requests;

use App\Http\Requests\BaseExportRequest;

class ExportCategoryWebsiteCMSRequest extends BaseExportRequest
{


    protected function getModelSpecificFilters(): array
    {
        $filters = [];

        if ($this->has('name')) {
            $filters['name'] = $this->get('name');
        }

        return $filters;
    }
}

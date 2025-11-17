<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Requests;

use App\Http\Requests\BaseExportRequest;

class ExportWebsiteServiceRequest extends BaseExportRequest
{
    protected function getModelSpecificRules(): array
    {
        return [
            'name' => 'sometimes|string',
            'reference_number' => 'sometimes|string',
            'category_website_cms_id' => 'sometimes|uuid',
        ];
    }

    protected function getModelSpecificFilters(): array
    {
        $filters = [];

        if ($this->has('name')) {
            $filters['name'] = $this->get('name');
        }

        if ($this->has('reference_number')) {
            $filters['reference_number'] = $this->get('reference_number');
        }

        if ($this->has('category_website_cms_id')) {
            $filters['category_website_cms_id'] = $this->get('category_website_cms_id');
        }

        return $filters;
    }
}

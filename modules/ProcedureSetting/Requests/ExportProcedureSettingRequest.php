<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use App\Http\Requests\BaseExportRequest;

class ExportProcedureSettingRequest extends BaseExportRequest
{
    protected function getModelSpecificRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
        ];
    }

    protected function getModelSpecificFilters(): array
    {
        $filters = [];
        
        if ($this->has('name')) {
            $filters['name'] = $this->get('name');
        }

        return $filters;
    }
}

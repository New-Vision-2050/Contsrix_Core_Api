<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ExportEcoProductDashboardRequest extends FormRequest
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

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }



    public function getFilters(): array
    {
        $filters = [];

        // Get format
        if ($this->has('format')) {
            $filters['format'] = $this->get('format');
        }

        // Get IDs filter
        if ($this->has('ids')) {
            $filters['ids'] = $this->get('ids');
        }

        // Get model-specific filters
        $modelFilters = $this->getModelSpecificFilters();

        return array_merge($filters, $modelFilters);
    }

    /**
     * Get model-specific filter rules
     * Override in child classes to add specific validation rules
     */
    protected function getModelSpecificRules(): array
    {
        return [];
    }

    /**
     * Get model-specific filters from request
     * Override in child classes to add specific filters
     */
    protected function getModelSpecificFilters(): array
    {
        return [];
    }

    /**
     * Merge model-specific rules with base rules
     */
    public function rules(): array
    {
        return array_merge([
            'format' => 'sometimes|string|in:xlsx,csv',
            'ids' => 'sometimes|array',
            'ids.*' => 'string|uuid',
        ], $this->getModelSpecificRules());
    }
}

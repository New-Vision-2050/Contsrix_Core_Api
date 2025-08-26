<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasExport
{
    /**
     * Get data for export with filters applied
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->where('company_id', tenant('id'));

        // Apply filters dynamically based on the model's fillable attributes
        foreach ($filters as $field => $value) {
            if ($field === 'ids' && is_array($value)) {
                $query->whereIn('id', $value);
                continue;
            }

            if ($field === 'format') {
                continue; // Skip format as it's not a database field
            }

            if (!empty($value) && in_array($field, $this->model->getFillable())) {
                if (is_string($value)) {
                    $query->where($field, 'LIKE', '%' . $value . '%');
                } else {
                    $query->where($field, $value);
                }
            }
        }

        // Load relationships if they exist
        $relationships = $this->getExportRelationships();
        if (!empty($relationships)) {
            $query->with($relationships);
        }

        return $query->get();
    }

    /**
     * Get relationships to load for export
     * Override in repository classes to specify relationships
     *
     * @return array
     */
    protected function getExportRelationships(): array
    {
        return [];
    }
}

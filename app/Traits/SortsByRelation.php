<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortsByRelation
{
    /**
     * Join a related table and order by its field.
     *
     * @param Builder $query
     * @param string $relatedTable         Table to join (e.g., 'job_types')
     * @param string $foreignKey          Foreign key in current table (e.g., 'job_type_id')
     * @param string $ownerKey            Primary key in related table (default 'id')
     * @param string $orderField          Field in the related table to order by (e.g., 'name')
     * @param string $order               Direction: asc | desc
     * @param string $currentTable        Optional current table name (default: from model)
     * @return Builder
     */
    public function scopeJoinAndOrderByRelationField(
        Builder $query,
        string $relatedTable,
        string $foreignKey,
        string $orderField,
        string $order = 'asc',
        string $ownerKey = 'id',
        ?string $currentTable = null
    ): Builder {
        $model = $query->getModel();
        $mainTable = $currentTable ?? $model->getTable();

        return $query->join($relatedTable, "{$mainTable}.{$foreignKey}", '=', "{$relatedTable}.{$ownerKey}")
                     ->orderBy("{$relatedTable}.{$orderField}", $order)
                     ->select("{$mainTable}.*");
    }
}

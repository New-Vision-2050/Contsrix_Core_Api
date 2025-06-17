<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortsByRelation
{
    /**
     * Scope a query to sort by a column on a related model.
     *
     * @param Builder $query
     * @param string $relationPath 'relation.column' or 'nested.relation.column'
     * @param string $order
     * @return Builder
     */
    public function scopeOrderByRelation(Builder $query, string $relationPath, string $order = 'asc'): Builder
    {
        $parts = explode('.', $relationPath);
        $column = array_pop($parts);
        $relationName = implode('.', $parts);

        $relationInstance = $this;
        foreach (explode('.', $relationName) as $relationSegment) {
            $relationInstance = $relationInstance->$relationSegment()->getRelated();
        }

        $relationTable = $relationInstance->getTable();
        $modelTable = $this->getTable();
        $relation = $this->$relationName();
        $foreignKey = $relation->getForeignKeyName();
        $ownerKey = $relation->getOwnerKeyName();

        // Ensure we only select from the main model's table to avoid ambiguous columns.
        // Eloquent will handle this correctly if we don't add selects from other tables.
        if (empty($query->getQuery()->columns)) {
            $query->select("{$modelTable}.*");
        }

        // Check if the column is translatable on the related model
        if (method_exists($relationInstance, 'isTranslatableAttribute') && $relationInstance->isTranslatableAttribute($column)) {
            $translationTableAlias = $relationTable . '_translations_sort';
            $query
                ->leftJoin($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                ->leftJoin("translations as {$translationTableAlias}", function ($join) use ($relationTable, $relationInstance, $column, $translationTableAlias) {
                    $join->on("{$translationTableAlias}.translatable_id", '=', "{$relationTable}.id")
                        ->where("{$translationTableAlias}.translatable_type", get_class($relationInstance))
                        ->where("{$translationTableAlias}.field", $column);
                })
                ->orderBy("{$translationTableAlias}.content", $order);
        } else {
            // Standard sort for non-translatable columns
            $query
                ->leftJoin($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                ->orderBy("{$relationTable}.{$column}", $order);
        }

        // Use GROUP BY on the primary key of the main table.
        // This is the correct way to remove duplicates from a one-to-many join.
        // It's compatible with strict SQL modes and the ORDER BY on the joined table.
        return $query->groupBy("{$modelTable}.{$this->getKeyName()}");
    }
}

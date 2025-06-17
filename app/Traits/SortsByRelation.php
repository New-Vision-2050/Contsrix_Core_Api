<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
        $query->distinct();

        // Split the path e.g., 'jobType.name' -> ['jobType', 'name']
        $parts = explode('.', $relationPath);
        $column = array_pop($parts);
        $relationName = implode('.', $parts);

        // Get the related model instance through the relationship
        $relationInstance = $this;
        foreach (explode('.', $relationName) as $relationSegment) {
            $relationInstance = $relationInstance->$relationSegment()->getRelated();
        }

        $relationTable = $relationInstance->getTable();
        $modelTable = $this->getTable();
        $relation = $this->$relationName(); // Get the relationship object
        $foreignKey = $relation->getForeignKeyName();
        $ownerKey = $relation->getOwnerKeyName();

        // Use addSelect to prevent wiping out other select statements (like from withCount)
        // and to avoid ambiguous column errors from the join.
        $query->addSelect("{$modelTable}.*");

        // Check if the column is translatable on the related model
        if (method_exists($relationInstance, 'isTranslatableAttribute') && $relationInstance->isTranslatableAttribute($column)) {
            $translationTableAlias = $relationTable . '_translations_sort';
            $query
                // Use LEFT JOIN to include JobTitles that have no JobType
                ->leftJoin($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                // Also use LEFT JOIN here in case a JobType has no translation
                ->leftJoin("translations as {$translationTableAlias}", function ($join) use ($relationTable, $relationInstance, $column, $translationTableAlias) {
                    $join->on("{$translationTableAlias}.translatable_id", '=', "{$relationTable}.id")
                        ->where("{$translationTableAlias}.translatable_type", get_class($relationInstance))
                        ->where("{$translationTableAlias}.field", $column);
                })
                ->orderBy("{$translationTableAlias}.content", $order);
        } else {
            // Standard sort for non-translatable columns
            $query
                // Use LEFT JOIN to include JobTitles that have no JobType
                ->leftJoin($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                ->orderBy("{$relationTable}.{$column}", $order);
        }

        return $query;
    }
}

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
        // Split the path e.g., 'jobType.name' -> ['jobType', 'name']
        $parts = explode('.', $relationPath);
        // The column to sort by is the last part
        $column = array_pop($parts);
        // The relation name is what's left
        $relationName = implode('.', $parts);

        // Get the related model instance through the relationship
        $relation = $this;
        foreach (explode('.', $relationName) as $relationSegment) {
            $relation = $relation->$relationSegment()->getRelated();
        }

        $relationTable = $relation->getTable();
        $modelTable = $this->getTable();
        $foreignKey = $this->$relationName()->getForeignKeyName();
        $ownerKey = $this->$relationName()->getOwnerKeyName();

        // Check if the column is translatable on the related model
        if (method_exists($relation, 'isTranslatableAttribute') && $relation->isTranslatableAttribute($column)) {
            $translationTableAlias = $relationTable . '_translations_sort';
            $query
                ->join($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                ->join("translations as {$translationTableAlias}", function ($join) use ($relationTable, $relation, $column, $translationTableAlias) {
                    $join->on("{$translationTableAlias}.translatable_id", '=', "{$relationTable}.id")
                        ->where("{$translationTableAlias}.translatable_type", get_class($relation))
                        ->where("{$translationTableAlias}.field", $column);
                })
                ->orderBy("{$translationTableAlias}.content", $order);
        } else {
            // Standard sort for non-translatable columns
            $query
                ->join($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                ->orderBy("{$relationTable}.{$column}", $order);
        }

        // Always select the original model's columns to avoid conflicts
        return $query->select("{$modelTable}.*");
    }
}

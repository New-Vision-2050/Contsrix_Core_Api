<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortsByRelation
{
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

        if (empty($query->getQuery()->columns)) {
            $query->select("{$modelTable}.*");
        }

        // Check if the column is translatable
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
            $query
                ->leftJoin($relationTable, "{$modelTable}.{$foreignKey}", '=', "{$relationTable}.{$ownerKey}")
                ->orderBy("{$relationTable}.{$column}", $order);
        }

        return $query->groupBy("{$modelTable}.{$this->getKeyName()}");
    }
}

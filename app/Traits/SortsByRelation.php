<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortsByRelation
{
    /**
     * Scope a query to sort by a column on a related model.
     * This scope can handle both standard columns and translated columns.
     *
     * @param Builder $query The Eloquent query builder instance.
     * @param string $relatedTable The name of the related table to join (e.g., 'job_types').
     * @param string $foreignKey The foreign key on the current model's table (e.g., 'job_type_id').
     * @param string $orderField The field on the related table to sort by (e.g., 'name' or 'created_at').
     * @param string $order The sort direction ('asc' or 'desc').
     * @param string $ownerKey The primary key on the related table (usually 'id').
     * @param bool $translated Set to true if the $orderField is in the translations table.
     * @param string|null $relatedModelClass The class name of the related model (required if $translated is true).
     * @return Builder
     */
    public function scopeOrderByRelation(
        Builder $query,
        string $relatedTable,
        string $foreignKey,
        string $orderField,
        string $order = 'asc',
        string $ownerKey = 'id',
        bool $translated = false,
        ?string $relatedModelClass = null
    ): Builder {
        $currentTable = $this->getTable();

        // --- التغيير الأول: استخدم leftJoin هنا ---
        $query->leftJoin($relatedTable, "{$currentTable}.{$foreignKey}", '=', "{$relatedTable}.{$ownerKey}");

        if ($translated) {
            if (!$relatedModelClass) {
                throw new \InvalidArgumentException('The $relatedModelClass parameter is required when sorting by a translated field.');
            }
            $translationTableAlias = $relatedTable . '_translations_sort';

            // --- التغيير الثاني: استخدم leftJoin هنا أيضاً ---
            $query->leftJoin("translations as {$translationTableAlias}", function ($join) use ($relatedTable, $relatedModelClass, $orderField, $translationTableAlias) {
                $join->on("{$translationTableAlias}.translatable_id", '=', "{$relatedTable}.id")
                    ->where("{$translationTableAlias}.translatable_type", $relatedModelClass)
                    ->where("{$translationTableAlias}.field", $orderField)
                    ->where("{$translationTableAlias}.locale", app()->getLocale());
            });

            $query->orderBy("{$translationTableAlias}.content", $order);

        } else {
            $query->orderBy("{$relatedTable}.{$orderField}", $order);
        }

        return $query->select("{$currentTable}.*");
    }
}

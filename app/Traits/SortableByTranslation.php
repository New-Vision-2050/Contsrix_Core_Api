<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortableByTranslation
{
    /**
     * Scope a query to sort by a translated attribute.
     *
     * @param Builder $query
     * @param string $field
     * @param string $order
     * @param string|null $locale
     * @return Builder
     */
    public function scopeOrderByTranslation(Builder $query, string $field, string $order = 'asc', string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();
        $modelTable = $this->getTable();
        $modelClass = self::class;

        $translationTableAlias = $modelTable . '_translations_sort';

        return $query->join("translations as {$translationTableAlias}", function ($join) use ($modelTable, $modelClass, $field, $locale, $translationTableAlias) {
                $join->on("{$translationTableAlias}.translatable_id", '=', "{$modelTable}.id")
                    ->where("{$translationTableAlias}.translatable_type", $modelClass)
                    ->where("{$translationTableAlias}.field", $field)
                    ->where("{$translationTableAlias}.locale", $locale);
            })
            ->orderBy("{$translationTableAlias}.content", $order)
            ->select("{$modelTable}.*");
    }
}

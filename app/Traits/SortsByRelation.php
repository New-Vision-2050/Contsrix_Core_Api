<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortableByTranslation
{
    /**
     * Scope a query to order by a translatable attribute.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column The translatable column to sort by (e.g., 'name').
     * @param string $order The sort direction ('asc' or 'desc').
     * @param string|null $locale The locale to sort by. Defaults to the current app locale.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByTranslation(Builder $query, string $column, string $order = 'asc', string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();

        $modelTable = $this->getTable();

        $query->leftJoin('translations', function ($join) use ($modelTable, $column, $locale) {
            $join->on('translations.translatable_id', '=', "{$modelTable}.id")
                ->where('translations.translatable_type', self::class) // self::class يشير إلى الموديل الذي يستخدم الـ Trait
                ->where('translations.field', $column)
                ->where('translations.locale', $locale);
        });

        // الترتيب حسب حقل المحتوى في جدول الترجمات
        $query->orderBy('translations.content', $order);

        $query->select("{$modelTable}.*")
              ->groupBy("{$modelTable}.{$this->getKeyName()}");

        return $query;
    }
}

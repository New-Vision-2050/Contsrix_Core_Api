<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UniqueTranslationRule implements Rule
{
    protected Model $model;
    protected string $column;
    protected string $locale;
    protected ?string $ignoreId;
    protected ?string $tenantColumn;


    public function __construct(
        Model $model,
        string $column,
        string $locale,
        ?string $ignoreId = null,
        ?string $tenantColumn = 'company_id'
    ) {
        $this->model = $model;
        $this->column = $column;
        $this->locale = $locale;
        $this->ignoreId = $ignoreId;
        $this->tenantColumn = $tenantColumn;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $query = $this->model::query()
            ->whereHas("translations",function ($q)use ($value){
                $q->where("locale", $this->locale)->where("content", $value)->where("field", $this->column);

            });

        // Add tenant scope if tenant column is specified
        if ($this->tenantColumn && function_exists('tenant')) {
            $query->where($this->tenantColumn, tenant('id'));
        }

        // Ignore specific ID for update operations
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return !$query->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        $localeLabel = $this->locale === 'ar' ? 'Arabic' : 'English';
        return "The {$localeLabel} {$this->column} has already been taken.";
    }
}

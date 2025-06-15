<?php

namespace Modules\SubEntity\Rules;

use Modules\SubEntity\Models\SubEntity;
use Illuminate\Contracts\Validation\Rule;
use Modules\SubEntity\Services\SuperEntityService;

class ValidSuperEntityId implements Rule
{
    public function passes($attribute, $value): bool
    {
        return in_array($value, $this->getValidSuperEntitiesIds(), true) ||
            SubEntity::where('id', $value)->active()->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Super entity Id.';
    }

    protected function getValidSuperEntitiesIds()
    {
        $superEntityService = app(SuperEntityService::class);
        return $superEntityService->getIds();
    }
}

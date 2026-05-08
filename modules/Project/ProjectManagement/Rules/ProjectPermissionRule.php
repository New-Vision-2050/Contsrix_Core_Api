<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Project\ProjectManagement\Enums\ProjectPermission;
use Modules\Project\ProjectManagement\Models\ProjectPermission as ProjectPermissionModel;

class ProjectPermissionRule implements Rule
{
    private string $type;
    private ?string $errorMessage = null;

    /**
     * Create a new rule instance.
     *
     * @param string $type 'key' to validate config keys, 'name' to validate permission names, 'any' for both
     */
    public function __construct(string $type = 'any')
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            $this->errorMessage = 'The permission must be a string.';
            return false;
        }

        switch ($this->type) {
            case 'key':
                return $this->validateConfigKey($value);
            
            case 'name':
                return $this->validatePermissionName($value);
            
            case 'any':
            default:
                return $this->validateConfigKey($value) || $this->validatePermissionName($value);
        }
    }

    /**
     * Validate if value is a valid config key
     */
    private function validateConfigKey(string $value): bool
    {
        if (!ProjectPermission::exists($value)) {
            $this->errorMessage = "The permission key '{$value}' does not exist in config.";
            return false;
        }

        return true;
    }

    /**
     * Validate if value is a valid permission name in database
     */
    private function validatePermissionName(string $value): bool
    {
        $exists = ProjectPermissionModel::where('name', $value)->exists();
        
        if (!$exists) {
            $this->errorMessage = "The permission name '{$value}' does not exist in database.";
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage ?? 'The :attribute is not a valid project permission.';
    }
}

<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Rules;


use Illuminate\Contracts\Validation\Rule;
use Modules\Subscription\Module\Models\Module;

class ValidModuleTree implements Rule
{
    protected array $errors = [];

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        if (!is_array($value)) {
            $this->errors[] = "The $attribute must be an array.";
            return false;
        }

        // Collect all module IDs from input
        $parentIds = array_keys($value);
        $childIds = [];

        foreach ($value as $parentId => $children) {
            if (is_array($children)) {
                foreach ($children as $childId) {
                    if ($childId) {
                        $childIds[] = $childId;
                    }
                }
            }
        }

        $allModuleIds = array_unique(array_merge($parentIds, $childIds));

        // Query all modules in one go
        $modules = Module::whereIn('id', values: $allModuleIds)->get()->keyBy('id');

        // Check parents
        foreach ($parentIds as $parentId) {
            if (!isset($modules[$parentId])) {
                $this->errors[] = "Parent module [$parentId] does not exist.";
            }
        }

        // Check children and parent-child relationship
        foreach ($value as $parentId => $children) {
            if (!is_array($children) && $children !== null) {
                $this->errors[] = "Children of parent module [$parentId] must be an array or null.";
                continue;
            }

            if (is_array($children)) {
                foreach ($children as $childId) {
                    if (!$childId) {
                        continue;
                    }

                    if (!isset($modules[$childId])) {
                        $this->errors[] = "Child module [$childId] does not exist.";
                        continue;
                    }

                    // Check if child's module_id matches the parent id
                    if ($modules[$childId]->module_id !== $parentId) {
                        $this->errors[] = "Child module [$childId] is not a valid child of parent module [$parentId].";
                    }
                }
            }
        }

        return count($this->errors) === 0;
    }


    /**
     * @return string
     */
    public function message()
    {
        return implode(' ', $this->errors) ?: 'The modules structure is invalid.';
    }
}

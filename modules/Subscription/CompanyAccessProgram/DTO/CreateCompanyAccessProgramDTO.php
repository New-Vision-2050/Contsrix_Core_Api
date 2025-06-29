<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\DTO;

class CreateCompanyAccessProgramDTO
{
    /**
     * @param string $name
     * @param array<string, array|null>|null $modules   Nested modules input
     * @param array<string>|null $companyFields         UUIDs
     * @param array<string>|null $companyTypes          UUIDs
     * @param array<int>|null $countries                Unsigned mediumints
     */
    public function __construct(
        public string $name,
        public array $modules = [],
        public ?array $companyFields = [],
        public ?array $companyTypes = [],
        public ?array $countries = [],
    ) {
        $this->modules = $this->normalizeModules($modules);
    }

    /**
     * Flatten nested modules array into unique list of module IDs.
     *
     * @param array<string, array|null> $modules
     * @return array<string>
     */
    protected function normalizeModules(array $modules): array
    {
        $flat = [];

        foreach ($modules as $parent => $children) {
            $flat[] = $parent;

            if (is_array($children)) {
                foreach ($children as $child) {
                    if ($child) {
                        $flat[] = $child;
                    }
                }
            }
        }

        return array_unique($flat);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'modules' => $this->modules,
            'company_fields' => $this->companyFields,
            'company_types' => $this->companyTypes,
            'countries' => $this->countries,
        ];
    }
}

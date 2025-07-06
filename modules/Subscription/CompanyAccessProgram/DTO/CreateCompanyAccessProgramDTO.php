<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\DTO;

class CreateCompanyAccessProgramDTO
{
    /**
     * @param string $name
     * @param array<string, mixed> $rawPrograms    Nested structure of programs with sub-entities
     * @param array<string>|null $companyFields
     * @param array<string>|null $companyTypes
     * @param array<int>|null $countries
     */
    public function __construct(
        public string $name,
        array $rawPrograms = [],
        public ?array $companyFields = [],
        public ?array $companyTypes = [],
        public ?array $countries = [],
        public array $programs = [],
        public array $subEntities = [],
    ) {
        [$this->programs, $this->subEntities] = $this->normalizePrograms($rawPrograms);
    }

    /**
     * Normalize programs and sub-entities from nested input.
     *
     * @param array<string, mixed> $programsInput
     * @return array{0: array<string>, 1: array<string>}
     */
    protected function normalizePrograms(array $input): array
    {
        $programs = [];
        $subEntities = [];

        $traverse = function (array $nodes) use (&$traverse, &$programs, &$subEntities) {
            foreach ($nodes as $programId => $programData) {
                $programs[] = $programId;

                if (!empty($programData['sub_entities']) && is_array($programData['sub_entities'])) {
                    foreach ($programData['sub_entities'] as $subEntityId) {
                        $subEntities[] = $subEntityId;
                    }
                }

                if (!empty($programData['children']) && is_array($programData['children'])) {
                    $traverse($programData['children']);
                }

                foreach ($programData as $key => $childNode) {
                    if (
                        is_array($childNode) &&
                        isset($childNode['sub_entities']) &&
                        !in_array($key, ['sub_entities', 'children'], true)
                    ) {
                        $programs[] = $key;
                        foreach ($childNode['sub_entities'] as $subEntityId) {
                            $subEntities[] = $subEntityId;
                        }
                    }
                }
            }
        };

        $traverse($input);

        return [array_unique($programs), array_unique($subEntities)];
    }


    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'programs' => $this->programs,
            'sub_entities' => $this->subEntities,
            'company_fields' => $this->companyFields,
            'company_types' => $this->companyTypes,
            'countries' => $this->countries,
        ];
    }
}

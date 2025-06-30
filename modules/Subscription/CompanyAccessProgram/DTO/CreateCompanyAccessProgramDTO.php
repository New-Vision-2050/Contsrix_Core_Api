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
    protected function normalizePrograms(array $programsInput): array
    {
        $programs = [];
        $subEntities = [];

        $traverse = function (array $nodes) use (&$traverse, &$programs, &$subEntities) {
            foreach ($nodes as $programId => $data) {
                $programs[] = $programId;

                if (!empty($data['sub_entities'])) {
                    foreach ($data['sub_entities'] as $subId) {
                        $subEntities[] = $subId;
                    }
                }

                if (!empty($data['children'])) {
                    $traverse($data['children']);
                }
            }
        };

        $traverse($programsInput);

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

<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\DTO;

class CreateCompanyAccessProgramDTO
{
    /**
     * @param string $name
     * @param ProgramPayloadDTO[] $rawPrograms
     * @param array<string> $companyFields
     * @param array<string> $companyTypes
     * @param array<int> $countries
     */
    public function __construct(
        public readonly string $name,
        public readonly array $rawPrograms,
        public readonly array $companyFields,
        public readonly array $companyTypes,
        public readonly array $countries
    ) {
    }
}

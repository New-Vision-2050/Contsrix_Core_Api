<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyAccessProgramCommand
{
    /**
     * @param \Modules\Subscription\CompanyAccessProgram\DTO\ProgramPayloadDTO[] $programs
     * @param string[] $companyFields
     * @param string[] $companyTypes
     * @param int[] $countries
     */
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private array $programs,
        private array $companyFields,
        private array $companyTypes,
        private array $countries,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return \Modules\Subscription\CompanyAccessProgram\DTO\ProgramPayloadDTO[]
     */
    public function getPrograms(): array
    {
        return $this->programs;
    }

    /**
     * @return string[]
     */
    public function getCompanyFields(): array
    {
        return $this->companyFields;
    }

    /**
     * @return string[]
     */
    public function getCompanyTypes(): array
    {
        return $this->companyTypes;
    }

    /**
     * @return int[]
     */
    public function getCountries(): array
    {
        return $this->countries;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'programs' => $this->programs,
            'companyFields' => $this->companyFields,
            'companyTypes' => $this->companyTypes,
            'countries' => $this->countries,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoLanguageDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public array $languages, // Array of language configurations
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'languages' => $this->languages,
        ];
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->companyId;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }
}

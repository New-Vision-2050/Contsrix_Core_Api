<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoBusinessActivityDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public UuidInterface $companyFieldId,
        public ?string $businessName = null,
        public ?string $commercialRegistrationNumber = null,
        public ?string $identityNumber = null,
        public ?string $ownerName = null,
        public ?string $nationalIdentityNumbers = null,
        public ?string $taxCertificateNumber = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'company_field_id' => $this->companyFieldId->toString(),
            'business_name' => $this->businessName,
            'commercial_registration_number' => $this->commercialRegistrationNumber,
            'identity_number' => $this->identityNumber,
            'owner_name' => $this->ownerName,
            'national_identity_numbers' => $this->nationalIdentityNumbers,
            'tax_certificate_number' => $this->taxCertificateNumber,
        ];
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->companyId;
    }
}

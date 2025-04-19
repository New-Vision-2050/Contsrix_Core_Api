<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\ProfessionalCertificate\DTO\CreateProfessionalCertificateDTO;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;
use Modules\UserInfo\ProfessionalCertificate\Repositories\ProfessionalCertificateRepository;
use Ramsey\Uuid\UuidInterface;

class ProfessionalCertificateCRUDService
{
    public function __construct(
        private ProfessionalCertificateRepository $repository,
    ) {
    }

    public function create(CreateProfessionalCertificateDTO $createProfessionalCertificateDTO): ProfessionalCertificate
    {
         return $this->repository->createProfessionalCertificate($createProfessionalCertificateDTO->toArray());
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10)//: array
    {
        return $this->repository->getProfessionalCertificateList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $id): ProfessionalCertificate
    {
        return $this->repository->getProfessionalCertificate(
            id: $id,
        );
    }
}

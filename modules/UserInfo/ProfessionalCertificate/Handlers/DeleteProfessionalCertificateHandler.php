<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Handlers;

use Modules\UserInfo\ProfessionalCertificate\Repositories\ProfessionalCertificateRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProfessionalCertificateHandler
{
    public function __construct(
        private ProfessionalCertificateRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProfessionalCertificate($id);
    }
}

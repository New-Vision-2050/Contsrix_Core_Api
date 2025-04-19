<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Handlers;

use Modules\UserInfo\ProfessionalCertificate\Commands\UpdateProfessionalCertificateCommand;
use Modules\UserInfo\ProfessionalCertificate\Repositories\ProfessionalCertificateRepository;

class UpdateProfessionalCertificateHandler
{
    public function __construct(
        private ProfessionalCertificateRepository $repository,
    ) {
    }

    public function handle(UpdateProfessionalCertificateCommand $updateProfessionalCertificateCommand)
    {
        $this->repository->updateProfessionalCertificate($updateProfessionalCertificateCommand->getId(), $updateProfessionalCertificateCommand->toArray());
    }
}

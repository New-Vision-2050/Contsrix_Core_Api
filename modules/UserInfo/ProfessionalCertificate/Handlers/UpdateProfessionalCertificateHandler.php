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
<<<<<<< HEAD
        $this->repository->updateProfessionalCertificate($updateProfessionalCertificateCommand->getId(), $updateProfessionalCertificateCommand->toArray(), $updateProfessionalCertificateCommand->file);
=======
        $this->repository->updateProfessionalCertificate($updateProfessionalCertificateCommand->getId(), $updateProfessionalCertificateCommand->toArray());
>>>>>>> 7be6c72c (merge with stage (first version ))
    }
}

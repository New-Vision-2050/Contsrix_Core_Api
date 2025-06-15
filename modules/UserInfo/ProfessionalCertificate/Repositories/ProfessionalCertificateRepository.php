<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
<<<<<<< HEAD
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Nonstandard\Uuid;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;

/**
 * @property ProfessionalCertificate $model
 * @method ProfessionalCertificate findOneOrFail($id)
 * @method ProfessionalCertificate findOneByOrFail(array $data)
 */
class ProfessionalCertificateRepository extends BaseRepository
{
<<<<<<< HEAD
    public function __construct(
        ProfessionalCertificate $model,
        private CompanyUserRepository $companyUserRepository,
        private FileUploadService $fileUploadService,

    )
=======
    public function __construct(ProfessionalCertificate $model)
>>>>>>> 7be6c72c (merge with stage (first version ))
    {
        parent::__construct($model);
    }

    public function getProfessionalCertificateList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10):array
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getProfessionalCertificate(UuidInterface $id): ProfessionalCertificate
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

<<<<<<< HEAD
    public function createProfessionalCertificate(array $data , $file = null): ProfessionalCertificate
    {
        $certificate = $this->create($data);
        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($data['global_id']));
        if ($file) {
            $certificate->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'])?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $this->fileUploadService->uploadFile(
                $certificate,
                $file,
                $path,
                'upload',
                "public"
            );
        }

        return $certificate;

    }

    public function updateProfessionalCertificate(UuidInterface $id, array $data , $file = null): bool
    {
        $certificate = $this->findOneBy(["id" => $id]);
        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($certificate->global_id));
        if ($file) {
            $certificate->clearMediaCollection('upload');
            $companyName = Company::find($certificate->company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $this->fileUploadService->uploadFile(
                $certificate,
                $file,
                $path,
                'upload',
                "public"
            );
        }


=======
    public function createProfessionalCertificate(array $data): ProfessionalCertificate
    {
        return $this->create($data);
    }

    public function updateProfessionalCertificate(UuidInterface $id, array $data): bool
    {
>>>>>>> 7be6c72c (merge with stage (first version ))
        return $this->update($id, $data);
    }

    public function deleteProfessionalCertificate(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveFileService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Nonstandard\Uuid;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;

/**
 * @property ProfessionalCertificate $model
 * @method ProfessionalCertificate findOneOrFail($id)
 * @method ProfessionalCertificate findOneByOrFail(array $data)
 */
class ProfessionalCertificateRepository extends BaseRepository
{
    public function __construct(
        ProfessionalCertificate $model,
        private CompanyUserRepository $companyUserRepository,
        private FileUploadService $fileUploadService,
        private EmployeeArchiveFileService $employeeArchiveFileService,

    )
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

    public function createProfessionalCertificate(array $data , $file = null): ProfessionalCertificate
    {
        $certificate = $this->create($data);
        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($data['global_id']));
        if ($file) {
            $certificate->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'])?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $media = $this->fileUploadService->uploadFile(
                $certificate,
                $file,
                $path,
                'upload',
                "public"
            );
            $this->employeeArchiveFileService->archiveUploadedFiles(
                companyId: (string) $data['company_id'],
                employeeGlobalId: (string) $data['global_id'],
                employeeName: (string) $user->name,
                files: $file,
                mainSection: EmployeeArchiveFileService::SECTION_ACADEMIC,
                subSection: EmployeeArchiveFileService::SUB_PROFESSIONAL_CERTIFICATES,
                sourceModel: $certificate,
                sourceMedia: $media,
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

            $media = $this->fileUploadService->uploadFile(
                $certificate,
                $file,
                $path,
                'upload',
                "public"
            );
            $this->employeeArchiveFileService->archiveUploadedFiles(
                companyId: (string) $certificate->company_id,
                employeeGlobalId: (string) $certificate->global_id,
                employeeName: (string) $user->name,
                files: $file,
                mainSection: EmployeeArchiveFileService::SECTION_ACADEMIC,
                subSection: EmployeeArchiveFileService::SUB_PROFESSIONAL_CERTIFICATES,
                sourceModel: $certificate,
                sourceMedia: $media,
            );
        }


        return $this->update($id, $data);
    }

    public function deleteProfessionalCertificate(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

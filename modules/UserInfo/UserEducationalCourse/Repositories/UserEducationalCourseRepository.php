<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveFileService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Nonstandard\Uuid;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;

/**
 * @property UserEducationalCourse $model
 * @method UserEducationalCourse findOneOrFail($id)
 * @method UserEducationalCourse findOneByOrFail(array $data)
 */
class UserEducationalCourseRepository extends BaseRepository
{
    public function __construct(
        UserEducationalCourse     $model,
        private FileUploadService $fileUploadService,
        private CompanyUserRepository $companyUserRepository,
        private EmployeeArchiveFileService $employeeArchiveFileService

    )
    {
        parent::__construct($model);
    }

    public function getUserEducationalCourseList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10)
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getUserEducationalCourse(UuidInterface $id): UserEducationalCourse
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserEducationalCourse(array $data, $file = null): UserEducationalCourse
    {
        $educationalCourse = $this->create($data);
        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($data['global_id']));
        if ($file) {
            $educationalCourse->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'])?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $media = $this->fileUploadService->uploadFile(
                $educationalCourse,
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
                subSection: EmployeeArchiveFileService::SUB_COURSES,
                sourceModel: $educationalCourse,
                sourceMedia: $media,
            );
        }

        return $educationalCourse;
    }

    public function updateUserEducationalCourse(UuidInterface $id, array $data , $file = null): bool
    {

        $educationalCourse = $this->findOneBy(["id" => $id]);
        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($educationalCourse->global_id));
        if ($file) {
            $educationalCourse->clearMediaCollection('upload');
            $companyName = Company::find($educationalCourse->company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $media = $this->fileUploadService->uploadFile(
                $educationalCourse,
                $file,
                $path,
                'upload',
                "public"
            );
            $this->employeeArchiveFileService->archiveUploadedFiles(
                companyId: (string) $educationalCourse->company_id,
                employeeGlobalId: (string) $educationalCourse->global_id,
                employeeName: (string) $user->name,
                files: $file,
                mainSection: EmployeeArchiveFileService::SECTION_ACADEMIC,
                subSection: EmployeeArchiveFileService::SUB_COURSES,
                sourceModel: $educationalCourse,
                sourceMedia: $media,
            );
        }


        return $this->update($id, $data);
    }

    public function deleteUserEducationalCourse(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

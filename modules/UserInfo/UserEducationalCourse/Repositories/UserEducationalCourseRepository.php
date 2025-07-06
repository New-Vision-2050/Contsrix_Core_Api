<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
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
        private CompanyUserRepository $companyUserRepository

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

            $this->fileUploadService->uploadFile(
                $educationalCourse,
                $file,
                $path,
                'upload',
                "public"
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

            $this->fileUploadService->uploadFile(
                $educationalCourse,
                $file,
                $path,
                'upload',
                "public"
            );
        }


        return $this->update($id, $data);
    }

    public function deleteUserEducationalCourse(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

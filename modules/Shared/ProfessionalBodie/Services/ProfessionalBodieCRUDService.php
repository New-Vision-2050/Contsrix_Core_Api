<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Services;

use Illuminate\Support\Collection;
use Modules\Shared\AcademicSpecialization\Repositories\AcademicSpecializationRepository;
use Modules\Shared\ProfessionalBodie\DTO\CreateProfessionalBodieDTO;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;
use Modules\Shared\ProfessionalBodie\Repositories\ProfessionalBodieRepository;
use Modules\UserInfo\Qualification\Repositories\QualificationRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

class ProfessionalBodieCRUDService
{
    public function __construct(
        private ProfessionalBodieRepository $repository,
        private QualificationRepository $qualificationRepository,
        private AcademicSpecializationRepository $academicSpecializationRepository
    ) {
    }

    public function create(CreateProfessionalBodieDTO $createProfessionalBodieDTO): ProfessionalBodie
    {
         return $this->repository->createProfessionalBodie($createProfessionalBodieDTO->toArray());
    }

    public function list($code,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ProfessionalBodie
    {
        return $this->repository->getProfessionalBodie(
            id: $id,
        );
    }

    public function getCode(UuidInterface $company_id ,UuidInterface $global_id)
    {
       $qualification =  $this->qualificationRepository->getUserQualification($company_id,$global_id);

       if($qualification){
             $academicSpecialization =  $this->academicSpecializationRepository->getAcademicSpecialization(Uuid::fromString($qualification->academic_specialization_id));
           return $academicSpecialization?->code;
       }
       return null;
    }

    public function getCodes(UuidInterface $company_id, UuidInterface $global_id)//: array
    {
        $qualifications = $this->qualificationRepository->getQualificationList($company_id, $global_id,1,200);

        $codes = [];

        foreach ($qualifications['data'] as $qualification) {
            if (!empty($qualification->academic_specialization_id)) {
                $academicSpecialization = $this->academicSpecializationRepository->getAcademicSpecialization(
                    Uuid::fromString($qualification->academic_specialization_id)
                );

                if ($academicSpecialization) {
                    $codes[] = $academicSpecialization->code;
                }
            }
        }

        return $codes;
    }

}

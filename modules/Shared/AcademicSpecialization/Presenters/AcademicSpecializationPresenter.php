<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Presenters;

use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AcademicSpecializationPresenter extends AbstractPresenter
{
    private AcademicSpecialization $academicSpecialization;

    public function __construct(AcademicSpecialization $academicSpecialization)
    {
        $this->academicSpecialization = $academicSpecialization;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->academicSpecialization->id,
            'name' => $this->academicSpecialization->name,
            'code' => $this->academicSpecialization->code,
            'academic_qualification_id' => $this->academicSpecialization->academic_qualification_id,
        ];
    }
}

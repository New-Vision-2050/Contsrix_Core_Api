<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Presenters;

use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AcademicQualificationPresenter extends AbstractPresenter
{
    private AcademicQualification $academicQualification;

    public function __construct(AcademicQualification $academicQualification)
    {
        $this->academicQualification = $academicQualification;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->academicQualification->id,
            'name' => $this->academicQualification->name,
        ];
    }
}

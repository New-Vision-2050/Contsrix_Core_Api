<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Presenters;

use Modules\UserInfo\Qualification\Models\Qualification;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class QualificationPresenter extends AbstractPresenter
{
    private Qualification $qualification;

    public function __construct(Qualification $qualification)
    {
        $this->qualification = $qualification;
    }

    protected function present(bool $isListing = false): array
    {
        // $firstMedia = $this->qualification->getFirstMedia('upload_Qualification');

        return [
            'id' => $this->qualification->id,
            'company_id' => $this->qualification->company_id,
            'global_id' => $this->qualification->global_id,

            'country_id' => $this->qualification->country_id,
            'country_name'=>$this->qualification->country?->name,

            'university_id' => $this->qualification->university_id,
            'university_name' => $this->qualification->university?->name,

            'academic_qualification_id' => $this->qualification->academic_qualification_id,
            'academic_qualification_name' => $this->qualification->academicQualification?->name,

            'academic_specialization_id' => $this->qualification->academic_specialization_id,
            'academic_specialization_name' => $this->qualification->academicSpecialization?->name,

            'study_rate' => $this->qualification->study_rate,
            'graduation_date' => $this->qualification->graduation_date,
            'files' => MediaPresenter::collection($this->qualification->getMedia('upload_Qualification')),
            // 'files' => $firstMedia ? (new MediaPresenter($firstMedia))->getData() : null,

        ];
    }
}

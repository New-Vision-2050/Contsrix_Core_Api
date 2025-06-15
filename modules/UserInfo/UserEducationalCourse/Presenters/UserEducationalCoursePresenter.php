<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Presenters;

<<<<<<< HEAD
use Modules\Shared\Media\Presenters\MediaPresenter;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserEducationalCoursePresenter extends AbstractPresenter
{
    private UserEducationalCourse $userEducationalCourse;

    public function __construct(UserEducationalCourse $userEducationalCourse)
    {
        $this->userEducationalCourse = $userEducationalCourse;
    }

    protected function present(bool $isListing = false): array
    {
<<<<<<< HEAD
        $media=$this->userEducationalCourse->getFirstMedia('upload');
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
        return [
            'id' => $this->userEducationalCourse->id,
            'company_name' => $this->userEducationalCourse->company_name,
            'authority' => $this->userEducationalCourse->authority,
            'name' => $this->userEducationalCourse->name,
            'institute' => $this->userEducationalCourse->institute,
            'certificate' => $this->userEducationalCourse->certificate,
            'date_obtain' => $this->userEducationalCourse->date_obtain,
            'date_end' => $this->userEducationalCourse->date_end,
<<<<<<< HEAD
            "file"=>$media != null ? (new MediaPresenter($media))->getData():null
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
        ];
    }
}

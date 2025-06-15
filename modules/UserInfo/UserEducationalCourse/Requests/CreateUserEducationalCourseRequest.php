<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserEducationalCourse\DTO\CreateUserEducationalCourseDTO;

class CreateUserEducationalCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|uuid',
<<<<<<< HEAD
            'company_name' => 'nullable|string',
            'authority' => 'nullable|string',
            'name' => 'nullable|string',
            'institute' => 'nullable|string',
            'certificate' => 'nullable|string',
            'date_obtain' => 'nullable|date',
            'date_end' => 'nullable|date',
            "file"=>"nullable|file",
=======
            'company_name' => 'required|string',
            'authority' => 'required|string',
            'name' => 'required|string',
            'institute' => 'required|string',
            'certificate' => 'required|string',
            'date_obtain' => 'required|date',
            'date_end' => 'required|date',
>>>>>>> 7be6c72c (merge with stage (first version ))
        ];
    }

    public function createCreateUserEducationalCourseDTO(): CreateUserEducationalCourseDTO
    {
        return new CreateUserEducationalCourseDTO(
            company_id: '',
            global_id: '',
            company_name: $this->get('company_name'),
            authority: $this->get('authority'),
            name: $this->get('name'),
            institute: $this->get('institute'),
            certificate: $this->get('certificate'),
            date_obtain: $this->get('date_obtain'),
            date_end: $this->get('date_end'),
<<<<<<< HEAD
            file: $this->file('file'),
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
        );
    }
}

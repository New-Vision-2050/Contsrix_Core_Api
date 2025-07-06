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
            'company_name' => 'nullable|string',
            'authority' => 'nullable|string',
            'name' => 'nullable|string',
            'institute' => 'nullable|string',
            'certificate' => 'nullable|string',
            'date_obtain' => 'nullable|date',
            'date_end' => 'nullable|date',
            "file"=>"nullable|file",
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
            file: $this->file('file'),
        );
    }
}

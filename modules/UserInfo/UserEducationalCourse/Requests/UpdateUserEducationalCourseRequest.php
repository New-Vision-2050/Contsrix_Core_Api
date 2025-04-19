<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserEducationalCourse\Commands\UpdateUserEducationalCourseCommand;
use Modules\UserInfo\UserEducationalCourse\Handlers\UpdateUserEducationalCourseHandler;

class UpdateUserEducationalCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => 'required|string',
            'authority' => 'required|string',
            'name' => 'required|string',
            'institute' => 'required|string',
            'certificate' => 'required|string',
            'date_obtain' => 'required|date',
            'date_end' => 'required|date',
        ];
    }

    public function createUpdateUserEducationalCourseCommand(): UpdateUserEducationalCourseCommand
    {
        return new UpdateUserEducationalCourseCommand(
            id: Uuid::fromString($this->route('id')),
            company_id: '',
            global_id: '',
            company_name: $this->get('company_name'),
            authority: $this->get('authority'),
            name: $this->get('name'),
            institute: $this->get('institute'),
            certificate: $this->get('certificate'),
            date_obtain: $this->get('date_obtain'),
            date_end: $this->get('date_end'),
        );
    }
}

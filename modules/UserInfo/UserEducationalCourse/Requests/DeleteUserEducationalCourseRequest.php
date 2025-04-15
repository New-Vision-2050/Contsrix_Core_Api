<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteUserEducationalCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

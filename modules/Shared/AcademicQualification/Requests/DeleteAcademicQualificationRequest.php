<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteAcademicQualificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

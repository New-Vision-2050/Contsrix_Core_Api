<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteAcademicSpecializationRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

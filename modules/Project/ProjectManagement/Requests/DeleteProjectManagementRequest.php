<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteProjectManagementRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

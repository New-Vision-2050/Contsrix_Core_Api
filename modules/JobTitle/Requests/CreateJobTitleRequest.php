<?php

declare(strict_types=1);

namespace Modules\JobTitle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\JobTitle\DTO\CreateJobTitleDTO;

class CreateJobTitleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateJobTitleDTO(): CreateJobTitleDTO
    {
        return new CreateJobTitleDTO(
            name: $this->get('name'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\JobType\DTO\CreateJobTypeDTO;

class CreateJobTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'status' => 'sometimes|boolean',
        ];
    }

    public function createCreateJobTypeDTO(): CreateJobTypeDTO
    {
        return new CreateJobTypeDTO(
            name: $this->get('name'),
            status: $this->get('status', true)
        );
    }
}

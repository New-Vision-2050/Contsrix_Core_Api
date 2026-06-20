<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stakeholder\DTO\CreateStakeholderDTO;

class CreateStakeholderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'status' => 'sometimes|integer|in:0,1',
        ];
    }

    public function createDTO(): CreateStakeholderDTO
    {
        return new CreateStakeholderDTO(
            name: $this->get('name'),
            status: $this->get('status'),
        );
    }
}

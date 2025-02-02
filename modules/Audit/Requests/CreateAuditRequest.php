<?php

declare(strict_types=1);

namespace Modules\Audit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Audit\DTO\CreateAuditDTO;

class CreateAuditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateAuditDTO(): CreateAuditDTO
    {
        return new CreateAuditDTO(
            name: $this->get('name'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;

class CreateManagementHierarchyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateManagementHierarchyDTO(): CreateManagementHierarchyDTO
    {
        return new CreateManagementHierarchyDTO(
            name: $this->get('name'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\ManagementHierarchy\DTO\GetNonCopiedHierarchiesDTO;

class GetNonCopiedHierarchiesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
            'name' => 'nullable|string|max:255',
        ];
    }

    public function createGetNonCopiedHierarchiesDTO(): GetNonCopiedHierarchiesDTO
    {
        return new GetNonCopiedHierarchiesDTO(
            page: (int)$this->get('page', 1),
            perPage: (int)$this->get('per_page', 10)
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\ManagementHierarchy\Commands\UpdateManagementHierarchyCommand;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHierarchyHandler;

class UpdateManagementHierarchyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateManagementHierarchyCommand(): UpdateManagementHierarchyCommand
    {
        return new UpdateManagementHierarchyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

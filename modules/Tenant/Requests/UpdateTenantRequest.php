<?php

declare(strict_types=1);

namespace Modules\Tenant\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Tenant\Commands\UpdateTenantCommand;
use Modules\Tenant\Handlers\UpdateTenantHandler;

class UpdateTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTenantCommand(): UpdateTenantCommand
    {
        return new UpdateTenantCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

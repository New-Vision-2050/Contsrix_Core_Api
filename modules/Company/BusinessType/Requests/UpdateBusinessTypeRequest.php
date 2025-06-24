<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\BusinessType\Commands\UpdateBusinessTypeCommand;
use Modules\Company\BusinessType\Handlers\UpdateBusinessTypeHandler;

class UpdateBusinessTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',   
        ];
    }

    public function createUpdateBusinessTypeCommand(): UpdateBusinessTypeCommand
    {
        return new UpdateBusinessTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            description: $this->get('description')
        );
    }
}

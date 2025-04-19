<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Bank\Commands\UpdateBankCommand;
use Modules\Shared\Bank\Handlers\UpdateBankHandler;

class UpdateBankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateBankCommand(): UpdateBankCommand
    {
        return new UpdateBankCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Test\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Test\Commands\UpdateTestCommand;
use Modules\Test\Handlers\UpdateTestHandler;

class UpdateTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTestCommand(): UpdateTestCommand
    {
        return new UpdateTestCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubEntity\Commands\UpdateSubEntityCommand;
use Modules\SubEntity\Handlers\UpdateSubEntityHandler;

class UpdateSubEntityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateSubEntityCommand(): UpdateSubEntityCommand
    {
        return new UpdateSubEntityCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

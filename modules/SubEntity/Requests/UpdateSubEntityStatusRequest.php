<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Commands\UpdateSubEntityStatusCommand;

class UpdateSubEntityStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => 'required|boolean'
        ];
    }

    public function createUpdateSubEntityStatusCommand(): UpdateSubEntityStatusCommand
    {
        return new UpdateSubEntityStatusCommand(
            id: Uuid::fromString($this->route('id')),
            isActive: (bool) $this->input(key: 'is_active')
        );
    }
}

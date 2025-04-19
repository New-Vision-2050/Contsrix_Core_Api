<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\ProfessionalBodie\Commands\UpdateProfessionalBodieCommand;
use Modules\Shared\ProfessionalBodie\Handlers\UpdateProfessionalBodieHandler;

class UpdateProfessionalBodieRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateProfessionalBodieCommand(): UpdateProfessionalBodieCommand
    {
        return new UpdateProfessionalBodieCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

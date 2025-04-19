<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\AcademicSpecialization\Commands\UpdateAcademicSpecializationCommand;
use Modules\Shared\AcademicSpecialization\Handlers\UpdateAcademicSpecializationHandler;

class UpdateAcademicSpecializationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateAcademicSpecializationCommand(): UpdateAcademicSpecializationCommand
    {
        return new UpdateAcademicSpecializationCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

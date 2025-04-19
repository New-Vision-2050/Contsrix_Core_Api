<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\AcademicQualification\Commands\UpdateAcademicQualificationCommand;
use Modules\Shared\AcademicQualification\Handlers\UpdateAcademicQualificationHandler;

class UpdateAcademicQualificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateAcademicQualificationCommand(): UpdateAcademicQualificationCommand
    {
        return new UpdateAcademicQualificationCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

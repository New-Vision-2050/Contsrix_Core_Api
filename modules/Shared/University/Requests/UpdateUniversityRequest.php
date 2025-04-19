<?php

declare(strict_types=1);

namespace Modules\Shared\University\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\University\Commands\UpdateUniversityCommand;

class UpdateUniversityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'country_iso2' => 'required|string',
            'url' => 'nullable|string',
        ];
    }

    public function createUpdateUniversityCommand(): UpdateUniversityCommand
    {
        return new UpdateUniversityCommand(
            id: Uuid::fromString($this->route('id')),
            countryIso2: $this->get('country_iso2'),
            name: $this->get('name'),
            url: $this->get('url'),
        );
    }
}

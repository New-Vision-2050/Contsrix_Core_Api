<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Language\Commands\UpdateLanguageCommand;
use Modules\Shared\Language\Handlers\UpdateLanguageHandler;

class UpdateLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateLanguageCommand(): UpdateLanguageCommand
    {
        return new UpdateLanguageCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

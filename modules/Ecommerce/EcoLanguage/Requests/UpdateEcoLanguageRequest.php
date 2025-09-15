<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoLanguage\Commands\UpdateEcoLanguageCommand;
use Modules\Ecommerce\EcoLanguage\Handlers\UpdateEcoLanguageHandler;

class UpdateEcoLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoLanguageCommand(): UpdateEcoLanguageCommand
    {
        return new UpdateEcoLanguageCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

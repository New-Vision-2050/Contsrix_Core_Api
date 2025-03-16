<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Currency\Commands\UpdateCurrencyCommand;
use Modules\Shared\Currency\Handlers\UpdateCurrencyHandler;

class UpdateCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateCurrencyCommand(): UpdateCurrencyCommand
    {
        return new UpdateCurrencyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

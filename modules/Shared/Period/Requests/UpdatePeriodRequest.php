<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Period\Commands\UpdatePeriodCommand;
use Modules\Shared\Period\Handlers\UpdatePeriodHandler;

class UpdatePeriodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePeriodCommand(): UpdatePeriodCommand
    {
        return new UpdatePeriodCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

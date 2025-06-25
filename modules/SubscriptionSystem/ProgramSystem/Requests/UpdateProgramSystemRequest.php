<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\ProgramSystem\Commands\UpdateProgramSystemCommand;
use Modules\SubscriptionSystem\ProgramSystem\Handlers\UpdateProgramSystemHandler;

class UpdateProgramSystemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateProgramSystemCommand(): UpdateProgramSystemCommand
    {
        return new UpdateProgramSystemCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

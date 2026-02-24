<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Project\TermServices\Commands\UpdateTermServicesCommand;
use Modules\Project\TermServices\Handlers\UpdateTermServicesHandler;

class UpdateTermServicesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTermServicesCommand(): UpdateTermServicesCommand
    {
        return new UpdateTermServicesCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

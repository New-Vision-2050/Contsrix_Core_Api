<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoBusinessActivity\Commands\UpdateEcoBusinessActivityCommand;
use Modules\Ecommerce\EcoBusinessActivity\Handlers\UpdateEcoBusinessActivityHandler;

class UpdateEcoBusinessActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoBusinessActivityCommand(): UpdateEcoBusinessActivityCommand
    {
        return new UpdateEcoBusinessActivityCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

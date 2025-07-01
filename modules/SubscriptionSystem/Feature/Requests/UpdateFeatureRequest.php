<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Feature\Commands\UpdateFeatureCommand;
use Modules\SubscriptionSystem\Feature\Handlers\UpdateFeatureHandler;

class UpdateFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateFeatureCommand(): UpdateFeatureCommand
    {
        return new UpdateFeatureCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

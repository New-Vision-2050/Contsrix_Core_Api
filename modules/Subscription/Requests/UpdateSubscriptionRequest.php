<?php

declare(strict_types=1);

namespace Modules\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\Commands\UpdateSubscriptionCommand;
use Modules\Subscription\Handlers\UpdateSubscriptionHandler;

class UpdateSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateSubscriptionCommand(): UpdateSubscriptionCommand
    {
        return new UpdateSubscriptionCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

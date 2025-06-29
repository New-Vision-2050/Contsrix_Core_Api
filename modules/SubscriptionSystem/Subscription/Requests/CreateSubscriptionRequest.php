<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Subscription\DTO\CreateSubscriptionDTO;

class CreateSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateSubscriptionDTO(): CreateSubscriptionDTO
    {
        return new CreateSubscriptionDTO(
            name: $this->get('name'),
        );
    }
}

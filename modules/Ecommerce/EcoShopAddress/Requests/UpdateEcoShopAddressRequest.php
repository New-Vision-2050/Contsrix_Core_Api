<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoShopAddress\Commands\UpdateEcoShopAddressCommand;
use Modules\Ecommerce\EcoShopAddress\Handlers\UpdateEcoShopAddressHandler;

class UpdateEcoShopAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoShopAddressCommand(): UpdateEcoShopAddressCommand
    {
        return new UpdateEcoShopAddressCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

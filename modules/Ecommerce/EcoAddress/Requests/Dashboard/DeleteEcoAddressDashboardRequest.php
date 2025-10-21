<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAddress\DTO\DeleteEcoAddressDTO;
use Ramsey\Uuid\Uuid;

class DeleteEcoAddressDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

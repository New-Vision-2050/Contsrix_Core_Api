<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoAddressListDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}

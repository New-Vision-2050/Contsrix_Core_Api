<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoProductListDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}

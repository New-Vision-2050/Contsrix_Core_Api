<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoCategoryListDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}

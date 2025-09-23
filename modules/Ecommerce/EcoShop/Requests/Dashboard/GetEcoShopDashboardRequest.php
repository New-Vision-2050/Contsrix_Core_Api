<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoShopDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

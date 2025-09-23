<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoReportListDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}

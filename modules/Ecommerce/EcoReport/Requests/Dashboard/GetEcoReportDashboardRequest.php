<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoReportDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

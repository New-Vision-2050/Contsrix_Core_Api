<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoBrandDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

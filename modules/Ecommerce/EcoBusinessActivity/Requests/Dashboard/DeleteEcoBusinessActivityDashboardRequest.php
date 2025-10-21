<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoBusinessActivityDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

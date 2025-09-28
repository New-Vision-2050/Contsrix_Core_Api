<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoAppSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

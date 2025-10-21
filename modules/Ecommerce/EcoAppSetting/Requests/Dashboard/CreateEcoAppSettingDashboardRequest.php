<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\CreateEcoAppSettingDashboardDTO;

class CreateEcoAppSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoAppSettingDTO(): CreateEcoAppSettingDashboardDTO
    {
        return new CreateEcoAppSettingDashboardDTO(
            name: $this->get('name'),
        );
    }
}

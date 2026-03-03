<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\TermSetting\DTO\UpdateTermSettingStatusDTO;

class UpdateTermSettingStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => 'required|in:0,1',
        ];
    }

    public function createUpdateTermSettingStatusDTO(): UpdateTermSettingStatusDTO
    {
        return new UpdateTermSettingStatusDTO(
            id: (int) $this->route('id'),
            isActive: (int) $this->get('is_active'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\TermSetting\DTO\UpdateTermSettingServicesDTO;

class UpdateTermSettingServicesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'term_service_ids' => 'required|array',
            'term_service_ids.*' => 'integer|exists:term_services,id',
        ];
    }

    public function createUpdateTermSettingServicesDTO(): UpdateTermSettingServicesDTO
    {
        return new UpdateTermSettingServicesDTO(
            id: (int) $this->route('id'),
            termServiceIds: $this->get('term_service_ids', []),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateDepartmentContractSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateDepartmentContractSettingDTO;

class UpdateDepartmentContractSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_all_data_visible' => ['sometimes', 'boolean'],
        ];
    }

    public function toCommand(int $projectTypeId): UpdateDepartmentContractSettingCommand
    {
        $dto = new UpdateDepartmentContractSettingDTO(
            is_all_data_visible:(int) $this->input('is_all_data_visible'),
        );

        return new UpdateDepartmentContractSettingCommand($projectTypeId, $dto);
    }
}

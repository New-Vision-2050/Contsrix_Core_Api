<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateProjectDataSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateProjectDataSettingDTO;

class UpdateProjectDataSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * All fields are optional - can update one or all fields
     */
    public function rules(): array
    {
        return [
            'is_reference_number' => ['sometimes', 'boolean'],
            'is_name_project' => ['sometimes', 'boolean'],
            'is_client' => ['sometimes', 'boolean'],
            'is_responsible_engineer' => ['sometimes', 'boolean'],
            'is_number_contract' => ['sometimes', 'boolean'],
            'is_central_cost' => ['sometimes', 'boolean'],
            'is_project_value' => ['sometimes', 'boolean'],
            'is_start_date' => ['sometimes', 'boolean'],
            'is_achievement_percentage' => ['sometimes', 'boolean'],
        ];
    }

    public function toCommand(int $projectTypeId): UpdateProjectDataSettingCommand
    {
        $dto = new UpdateProjectDataSettingDTO(
            is_reference_number: $this->has('is_reference_number') ? (int) $this->get('is_reference_number') : null,
            is_name_project: $this->has('is_name_project') ? (int) $this->get('is_name_project') : null,
            is_client: $this->has('is_client') ? (int) $this->get('is_client') : null,
            is_responsible_engineer: $this->has('is_responsible_engineer') ? (int) $this->get('is_responsible_engineer') : null,
            is_number_contract: $this->has('is_number_contract') ? (int) $this->get('is_number_contract') : null,
            is_central_cost: $this->has('is_central_cost') ? (int) $this->get('is_central_cost') : null,
            is_project_value: $this->has('is_project_value') ? (int) $this->get('is_project_value') : null,
            is_start_date: $this->has('is_start_date') ? (int) $this->get('is_start_date') : null,
            is_achievement_percentage: $this->has('is_achievement_percentage') ? (int) $this->get('is_achievement_percentage') : null,
        );

        return new UpdateProjectDataSettingCommand($projectTypeId, $dto);
    }
}

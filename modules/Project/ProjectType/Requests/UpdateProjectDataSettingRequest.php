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
            is_reference_number: (int) $this->input('is_reference_number'),
            is_name_project: (int) $this->input('is_name_project'),
            is_client: (int) $this->input('is_client'),
            is_responsible_engineer: (int) $this->input('is_responsible_engineer'),
            is_number_contract: (int) $this->input('is_number_contract'),
            is_central_cost: (int) $this->input('is_central_cost'),
            is_project_value: (int) $this->input('is_project_value'),
            is_start_date: (int) $this->input('is_start_date'),
            is_achievement_percentage: (int) $this->input('is_achievement_percentage'),
        );

        return new UpdateProjectDataSettingCommand($projectTypeId, $dto);
    }
}

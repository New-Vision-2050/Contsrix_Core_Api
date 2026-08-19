<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateConstructionSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateConstructionSettingDTO;

class UpdateConstructionSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array { return ['is_shown' => ['sometimes', 'boolean']]; }

    public function toCommand(int $projectTypeId): UpdateConstructionSettingCommand
    {
        return new UpdateConstructionSettingCommand($projectTypeId, new UpdateConstructionSettingDTO(
            is_shown: $this->has('is_shown') ? (bool) $this->input('is_shown') : null,
        ));
    }
}
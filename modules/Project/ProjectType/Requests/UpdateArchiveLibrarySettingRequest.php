<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateArchiveLibrarySettingCommand;
use Modules\Project\ProjectType\DTO\UpdateArchiveLibrarySettingDTO;

class UpdateArchiveLibrarySettingRequest extends FormRequest
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

    public function toCommand(int $projectTypeId): UpdateArchiveLibrarySettingCommand
    {
        $dto = new UpdateArchiveLibrarySettingDTO(
            is_all_data_visible: $this->has('is_all_data_visible') ? (int) $this->input('is_all_data_visible') : null,
        );

        return new UpdateArchiveLibrarySettingCommand($projectTypeId, $dto);
    }
}

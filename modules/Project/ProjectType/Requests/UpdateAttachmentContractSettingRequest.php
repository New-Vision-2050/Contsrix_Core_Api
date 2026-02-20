<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateAttachmentContractSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateAttachmentContractSettingDTO;

class UpdateAttachmentContractSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_name' => ['sometimes', 'boolean'],
            'is_type' => ['sometimes', 'boolean'],
            'is_size' => ['sometimes', 'boolean'],
            'is_creator' => ['sometimes', 'boolean'],
            'is_create_date' => ['sometimes', 'boolean'],
            'is_downloadable' => ['sometimes', 'boolean'],
        ];
    }

    public function toCommand(int $projectTypeId): UpdateAttachmentContractSettingCommand
    {
        $dto = new UpdateAttachmentContractSettingDTO(
            is_name: $this->has('is_name') ? (int) $this->input('is_name') : null,
            is_type: $this->has('is_type') ? (int) $this->input('is_type') : null,
            is_size: $this->has('is_size') ? (int) $this->input('is_size') : null,
            is_creator: $this->has('is_creator') ? (int) $this->input('is_creator') : null,
            is_create_date: $this->has('is_create_date') ? (int) $this->input('is_create_date') : null,
            is_downloadable: $this->has('is_downloadable') ? (int) $this->input('is_downloadable') : null,
        );

        return new UpdateAttachmentContractSettingCommand($projectTypeId, $dto);
    }
}

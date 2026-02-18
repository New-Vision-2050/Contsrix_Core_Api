<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateAttachmentTermsContractSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateAttachmentTermsContractSettingDTO;

class UpdateAttachmentTermsContractSettingRequest extends FormRequest
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

    public function toCommand(int $projectTypeId): UpdateAttachmentTermsContractSettingCommand
    {
        $dto = new UpdateAttachmentTermsContractSettingDTO(
            is_name: $this->input('is_name'),
            is_type: $this->input('is_type'),
            is_size: $this->input('is_size'),
            is_creator: $this->input('is_creator'),
            is_create_date: $this->input('is_create_date'),
            is_downloadable: $this->input('is_downloadable'),
        );

        return new UpdateAttachmentTermsContractSettingCommand($projectTypeId, $dto);
    }
}

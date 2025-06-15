<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Modules\SubEntity\Models\SubEntity;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Services\SuperEntityService;
use Modules\SubEntity\Commands\UpdateSuperEntityAttributesConfigCommand;

class UpdateSuperEntityAttributesConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'default_attributes' => 'required|array',
            'default_attributes.*' => [Rule::In($this->getValidSuperEntityAttributes())],
            'optional_attributes' => 'required|array',
            'optional_attributes.*' => [Rule::In($this->getValidSuperEntityAttributes())],
        ];
    }

    public function createUpdateSuperEntityAttributesConfigCommand(): UpdateSuperEntityAttributesConfigCommand
    {
        return new UpdateSuperEntityAttributesConfigCommand(
            id: $this->route('id'),
            defaultAttributes: $this->input('default_attributes'),
            optionalAttributes: $this->input('optional_attributes')
        );
    }

    protected function getValidSuperEntityAttributes()
    {
        $superEntityService = app(SuperEntityService::class);
        $superEntityModel = $superEntityService->getModelForId($this->route('id'));
        return $superEntityModel ? $superEntityModel::getSubEntitiesAvailableAttributes() : [];
    }
}

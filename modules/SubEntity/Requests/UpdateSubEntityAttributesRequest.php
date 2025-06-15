<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Services\SuperEntityService;
use Modules\SubEntity\Commands\UpdateSubEntityAttributesCommand;
use Modules\SubEntity\Models\SubEntity;

class UpdateSubEntityAttributesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'default_attributes' => 'required|array',
            'default_attributes.*' => [Rule::In($this->getValidSuperEntityAttributes())],
            'optional_attributes' => 'sometimes|nullable|array',
            'optional_attributes.*' => [Rule::In($this->getValidSuperEntityAttributes())],
        ];
    }

    public function createUpdateSubEntityAttributesCommand(): UpdateSubEntityAttributesCommand
    {
        return new UpdateSubEntityAttributesCommand(
            id: Uuid::fromString($this->route('id')),
            default_attributes: $this->input('default_attributes'),
            optional_attributes: $this->input('optional_attributes')
        );
    }

    protected function getValidSuperEntityAttributes()
    {
        $superEntityId  = SubEntity::find($this->route('id'), ['id', 'super_entity'])?->super_entity;

        if(empty($superEntityId)) {
            abort(404, 'Super Entity Not Found!');
        }

        $superEntityService = app(SuperEntityService::class);
        $superEntityModel = $superEntityService->getModelForId($superEntityId );
        return $superEntityModel ? $superEntityModel::getSubEntitiesAvailableAttributes() : [];
    }
}

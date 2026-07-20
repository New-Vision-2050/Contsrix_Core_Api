<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

class UpdateProjectProcedureRequest extends StoreProjectProcedureRequest
{
    public function rules(): array
    {
        return array_merge($this->procedureRules(required: false), $this->metadataRules());
    }
}

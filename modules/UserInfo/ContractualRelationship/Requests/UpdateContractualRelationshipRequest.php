<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\UserInfo\ContractualRelationship\Commands\UpdateContractualRelationshipCommand;

class UpdateContractualRelationshipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contractual_relationship_type_id' => 'required|uuid|exists:contractual_relationship_types,id',
            'employment_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
        ];
    }

    public function createUpdateContractualRelationshipCommand(): UpdateContractualRelationshipCommand
    {
        return new UpdateContractualRelationshipCommand(
            company_id: '',
            global_id: '',
            contractual_relationship_type_id: $this->get('contractual_relationship_type_id'),
            employment_name: $this->get('employment_name'),
            registration_number: $this->get('registration_number'),
        );
    }
}

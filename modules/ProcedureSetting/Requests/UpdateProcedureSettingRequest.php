<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ProcedureSetting\Commands\UpdateProcedureSettingCommand;

class UpdateProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'type'         => 'required|string|in:client_request,price_offer,contract',
            'execute_type' => 'required|string|in:parallel,sequence',
            'icon'         => 'nullable|string|max:255',
            'percentage'   => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function createUpdateProcedureSettingCommand(): UpdateProcedureSettingCommand
    {
        return new UpdateProcedureSettingCommand(
            id:           Uuid::fromString($this->route('id')),
            name:         $this->get('name'),
            type:         $this->get('type'),
            execute_type: $this->get('execute_type'),
            icon:         $this->get('icon'),
            percentage:   $this->has('percentage') ? (float) $this->get('percentage') : null,
        );
    }
}

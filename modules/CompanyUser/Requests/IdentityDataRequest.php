<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\UpdateIdentityDataCommand;

class IdentityDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'passport' => 'nullable|string',
            'identity' => 'nullable|string',
            'border_number' => 'nullable|string',
            'entry_number' => 'nullable|string',
            'file_passport' => 'nullable|file',
            'file_identity' => 'nullable|file',
            'file_border_number' => 'nullable|file',
            'file_entry_number' => 'nullable|file',
        ];
    }

    public function updateIdentityDataCommand(): UpdateIdentityDataCommand
    {
        return new UpdateIdentityDataCommand(
            passport: $this->get('passport'),
            identity: $this->get('identity'),
            border_number: $this->get('border_number'),
            entry_number: $this->get('entry_number'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Audit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Audit\Commands\UpdateAuditCommand;
use Modules\Audit\Handlers\UpdateAuditHandler;

class UpdateAuditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateAuditCommand(): UpdateAuditCommand
    {
        return new UpdateAuditCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}

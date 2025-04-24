<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserStatus\Commands\UpdateUserStatusCommand;
use Modules\UserInfo\UserStatus\Handlers\UpdateUserStatusHandler;

class UpdateUserStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'active_type' => 'required|in:permanente_active,temporary_active',
            'active_date_to' => [
                'required_if:active_type,temporary_active',
                'date',
                'after:today',
            ],
        ];
    }

    public function createUpdateUserStatusCommand(): UpdateUserStatusCommand
    {
        return new UpdateUserStatusCommand(
            id: Uuid::fromString($this->route('id')),
            active_type: $this->get('active_type'),
            active_date_to:$this->get('active_date_to')
        );
    }
}

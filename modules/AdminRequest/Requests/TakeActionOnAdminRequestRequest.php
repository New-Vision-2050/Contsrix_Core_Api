<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AdminRequest\Enum\AdminRequestStatus;
use Ramsey\Uuid\Uuid;
use Modules\AdminRequest\Commands\TakeActionOnAdminRequestCommand;
use Modules\AdminRequest\Handlers\TakeActionAdminRequestHandler;

class TakeActionOnAdminRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:' . implode(',', [AdminRequestStatus::ACTIVE->value, AdminRequestStatus::INACTIVE->value]),
        ];
    }

    public function createUpdateAdminRequestCommand(): TakeActionOnAdminRequestCommand
    {
        return new TakeActionOnAdminRequestCommand(
            id: Uuid::fromString($this->route('id')),
            status:(string) $this->get('status'),
        );
    }
}

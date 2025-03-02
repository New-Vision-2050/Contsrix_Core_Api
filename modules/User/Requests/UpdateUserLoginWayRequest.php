<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Commands\UpdateUserLoginWayCommand;
use Ramsey\Uuid\Uuid;
use Modules\User\Commands\UpdateUserCommand;
use Modules\User\Handlers\UpdateUserHandler;

class UpdateUserLoginWayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "login_way_id" => "required|exists:login_ways,id",

        ];
    }

    public function createUpdateUserLoginWayCommand()
    {
        return new UpdateUserLoginWayCommand(
            id: Uuid::fromString($this->route('id')),
            loginWayId: Uuid::fromString($this->get('login_way_id'))
        );
    }
}

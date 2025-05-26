<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetUserByGlobalIdRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "global_id" => "required|exists:users,global_company_user_id,company_id,".tenant("id"),
            "role"=> "required"
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class LogoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [

        ];
    }

//    public function cre()
//    {
//
//    }


}

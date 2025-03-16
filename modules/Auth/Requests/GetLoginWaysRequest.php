<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\GetLoginWaysDTO;
use Modules\Auth\DTO\LoginDTO;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\Uuid;

class GetLoginWaysRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'identifier' => 'required'
        ];
    }

    public function createGetLoginWaysDTO(): GetLoginWaysDTO
    {
        return new GetLoginWaysDTO (
            identifier: $this->get('identifier')
        );
    }
}

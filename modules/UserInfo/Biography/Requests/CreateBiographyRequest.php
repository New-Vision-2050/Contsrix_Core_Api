<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\Biography\DTO\CreateBiographyDTO;

class CreateBiographyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|file',
            'user_id' => 'required|string',
        ];
    }

    public function createCreateBiographyDTO(): CreateBiographyDTO
    {
        return new CreateBiographyDTO(
            file: $this->file('file'),
            company_id: '',
            global_id: '',
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserAbout\DTO\CreateUserAboutDTO;

class CreateUserAboutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id'=>'required|string',
            'about_me' =>'required|string'
        ];
    }

    public function createCreateUserAboutDTO(): CreateUserAboutDTO
    {
        return new CreateUserAboutDTO(
            about_me: $this->get('about_me'),
            company_id: '',//get from controller
            global_id: '',
        );
    }
}

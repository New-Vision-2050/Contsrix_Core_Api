<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\question;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\DTO\GetUserQuestionsDTO;
use Ramsey\Uuid\Uuid;

class GetQuestionAnswerdForUserRequest extends FormRequest
{
    public function rules(): array
    {
       return [
           "user_id"=>"required"
        ];
    }

    public function createGetUserQuestionDTO()
    {
        return new GetUserQuestionsDTO(

            userId: Uuid::fromString($this->get('user_id'))
        );
    }


}

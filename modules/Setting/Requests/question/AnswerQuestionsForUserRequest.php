<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\question;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class AnswerQuestionsForUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ".*.question_id"=>"required|exists:questions,id",
            ".*.answer"=>"required|string",
        ];
    }
}

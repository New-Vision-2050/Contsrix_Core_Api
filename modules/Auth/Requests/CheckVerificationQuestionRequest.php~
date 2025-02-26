<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Modules\Setting\Models\Setting;

class CheckVerificationQuestionRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "identifier" => "required",
            'question_and_answers.*.question' => 'required',
            'question_and_answers.*.answer' => 'required',
        ];
    }

    public function createLoginDTO(): LoginDTO
    {
        return new LoginDTO(
            email: $this->get('email'),
            password: $this->get('password')
        );
    }
}

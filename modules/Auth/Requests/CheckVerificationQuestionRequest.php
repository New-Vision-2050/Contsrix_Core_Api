<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\CheckLoginDTO;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\QuestionVerificationDTO;
use Modules\Setting\Models\Setting;

class CheckVerificationQuestionRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "identifier" => "required",
            'questions_and_answers.*.question' => 'required',
            'questions_and_answers.*.answer' => 'required',
        ];
    }

    public function createLoginDTO(): QuestionVerificationDTO
    {
        return new QuestionVerificationDTO(
            identifier: $this->get('identifier'),
            questionsAndAnswers: $this->get('question_and_answers')
        );
    }
}

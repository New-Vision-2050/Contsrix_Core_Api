<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\question;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\Commands\Question\AnswerQuestionsByUserCommand;
use Ramsey\Uuid\Uuid;

class AnswerQuestionsForUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "*.question_id" => "required|exists:question_settings,id",
            "*.answer" => "required|string",
        ];
    }

    public function createAnswerQuestionsForUserCommand(): AnswerQuestionsByUserCommand
    {
        return new AnswerQuestionsByUserCommand(
            questionIdsAndAnswers: $this->validated()
        );

    }
}

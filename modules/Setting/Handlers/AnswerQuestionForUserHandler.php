<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Auth\Repositories\VerficationQuestionRepository;
use Modules\Setting\Commands\Question\AnswerQuestionsByUserCommand;


class AnswerQuestionForUserHandler
{
    public function __construct(
        private VerficationQuestionRepository $verficationQuestionRepository,
    ) {
    }

    public function handle(AnswerQuestionsByUserCommand $answerQuestionsByUserCommand)
    {
         $this->verficationQuestionRepository->createVerficationQuestion($answerQuestionsByUserCommand->getQuestionIdsAndAnswers());
    }
}

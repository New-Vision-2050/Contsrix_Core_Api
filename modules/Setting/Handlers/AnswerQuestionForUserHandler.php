<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Auth\Repositories\VerficationQuestionRepository;
use Modules\Setting\Commands\Question\AnswerQuestionsByUserCommand;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

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

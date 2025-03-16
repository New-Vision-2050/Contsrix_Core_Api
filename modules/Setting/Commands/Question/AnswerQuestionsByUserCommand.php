<?php

declare(strict_types=1);

namespace Modules\Setting\Commands\Question;

use Modules\Setting\Commands\Drivers\DriverCommand;
use Ramsey\Uuid\UuidInterface;

class AnswerQuestionsByUserCommand
{
    public function __construct(
        private $questionIdsAndAnswers
    )
    {
    }

    public function getQuestionIdsAndAnswers()
    {
        return $this->questionIdsAndAnswers;
    }


}

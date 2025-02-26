<?php

namespace Modules\Auth\DTO;

class QuestionVerificationDTO
{
    public function __construct(
        private string $identifier,
        private array $questionsAndAnswers,
    ) {
    }



    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getquestionsAndAnswers()
    {
        return $this->questionsAndAnswers;
    }
}

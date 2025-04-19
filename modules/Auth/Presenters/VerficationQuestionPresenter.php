<?php

declare(strict_types=1);

namespace Modules\Auth\Presenters;

use Modules\Auth\Models\VerificationQuestion;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class VerficationQuestionPresenter extends AbstractPresenter
{
    private VerificationQuestion $verificationQuestion;

    public function __construct(VerificationQuestion $verificationQuestion)
    {
        $this->verificationQuestion = $verificationQuestion;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->verificationQuestion->question->id,
            'question' => $this->verificationQuestion->question->question,
        ];
    }
}

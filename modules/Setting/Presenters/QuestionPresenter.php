<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\Driver;
use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Models\QuestionSetting;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class QuestionPresenter extends AbstractPresenter
{

    public function __construct(public QuestionSetting $questionSetting)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            "id" => $this->questionSetting->id,
            'question' => $this->questionSetting->question,
        ];
    }
}

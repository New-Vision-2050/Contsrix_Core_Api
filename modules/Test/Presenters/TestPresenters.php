<?php

declare(strict_types=1);

namespace Modules\Test\Presenters;

use Modules\Test\Models\Test;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TestPresenter extends AbstractPresenter
{
    private Test $test;

    public function __construct(Test $test)
    {
        $this->test = $test;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->test->id,
            'name' => $this->test->name,
        ];
    }
}

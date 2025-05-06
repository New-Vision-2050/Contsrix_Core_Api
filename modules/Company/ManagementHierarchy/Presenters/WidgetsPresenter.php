<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Illuminate\Contracts\Support\Arrayable;

class WidgetsPresenter extends AbstractPresenter
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }


    protected function present(bool $isListing = false): ?array
    {
        return [
            'users' => $this->data['users'] ?? [],
            'branches' => $this->data['branches'] ?? [],
            'management' => $this->data['management'] ?? [],
            'departments' => $this->data['departments'] ?? []
        ];
    }
}

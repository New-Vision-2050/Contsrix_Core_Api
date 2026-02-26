<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequestType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestTypePresenter extends AbstractPresenter
{
    private ClientRequestType $type;

    public function __construct(ClientRequestType $type)
    {
        $this->type = $type;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->type->id,
            'name' => $this->type->name,
            'type' => $this->type->type,
            'created_at' => $this->type->created_at?->toDateTimeString(),
            'updated_at' => $this->type->updated_at?->toDateTimeString(),
        ];
    }
}

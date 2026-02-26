<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequestReceiverFrom;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestReceiverFromPresenter extends AbstractPresenter
{
    private ClientRequestReceiverFrom $receiverFrom;

    public function __construct(ClientRequestReceiverFrom $receiverFrom)
    {
        $this->receiverFrom = $receiverFrom;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->receiverFrom->id,
            'name' => $this->receiverFrom->name,
            'type' => $this->receiverFrom->type,
            'created_at' => $this->receiverFrom->created_at?->toDateTimeString(),
            'updated_at' => $this->receiverFrom->updated_at?->toDateTimeString(),
        ];
    }
}

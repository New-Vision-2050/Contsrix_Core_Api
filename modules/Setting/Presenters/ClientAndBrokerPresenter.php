<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientAndBrokerPresenter extends AbstractPresenter
{
    private  $setting;

    public function __construct( $setting)
    {
        $this->setting = $setting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'is_share_client' => $this->setting->where("key","is_share_client")->first()->value,
            'is_share_broker' =>  $this->setting->where("key","is_share_broker")->first()->value,
        ];
    }
}

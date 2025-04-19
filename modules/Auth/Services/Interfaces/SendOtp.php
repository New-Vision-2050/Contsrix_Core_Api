<?php

namespace Modules\Auth\Services\Interfaces;

use Ramsey\Uuid\UuidInterface;

interface SendOtp
{
    public function send(UuidInterface $userId);
}

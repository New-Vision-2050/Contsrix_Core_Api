<?php

declare(strict_types=1);

namespace Modules\Setting\Commands\Drivers;

use Ramsey\Uuid\UuidInterface;

class UpdateMailCommand implements DriverCommand
{
    public function __construct(
        private UuidInterface $id,
        private string        $mailDriver,
        private string        $mailHost,
        private string        $mailPort,
        private string        $mailUsername,
        private string        $mailPassword,
        private string        $mailEncryption,
        private string        $mailAddress,
        private string        $mailFromName,
    )
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'MAIL_DRIVER' => $this->mailDriver,
            'MAIL_HOST' => $this->mailHost,
            'MAIL_PORT' => $this->mailPort,
            'MAIL_USERNAME' => $this->mailUsername,
            'MAIL_PASSWORD' => $this->mailPassword,
            'MAIL_ENCRYPTION' => $this->mailEncryption,
            'MAIL_FROM_ADDRESS' => $this->mailAddress,
            'MAIL_FROM_NAME' => $this->mailFromName
        ];
    }
}

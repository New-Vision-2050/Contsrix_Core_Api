<?php

declare(strict_types=1);

namespace Modules\Setting\Commands\Drivers;

use Ramsey\Uuid\UuidInterface;

class UpdateMailCommand implements DriverCommand
{
    public function __construct(
        private UuidInterface $id,
        private string        $mailMailer,
        private string        $mailHost,
        private string        $mailPort,
        private string        $mailUsername,
        private string        $mailPassword
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
            'mail_mailer' => $this->mailMailer,
            'mail_host' => $this->mailHost,
            'mail_port' => $this->mailPort,
            'mail_username' => $this->mailUsername,
            'mail_password' => $this->mailPassword
        ];
    }
}

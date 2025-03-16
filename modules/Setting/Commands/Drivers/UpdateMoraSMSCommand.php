<?php

declare(strict_types=1);

namespace Modules\Setting\Commands\Drivers;

use Ramsey\Uuid\UuidInterface;

class UpdateMoraSMSCommand implements DriverCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $smsMoraKey,
        private string $smsMoraUser,
        private string $smsMoraSender,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }



    public function toArray(): array
    {
        return [
            'sms_mora_key' => $this->smsMoraKey,
            'sms_mora_user' => $this->smsMoraUser,
            'sms_mora_sender' => $this->smsMoraSender,
        ];
    }
}

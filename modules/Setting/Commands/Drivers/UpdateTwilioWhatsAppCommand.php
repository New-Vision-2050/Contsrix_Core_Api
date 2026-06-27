<?php

declare(strict_types=1);

namespace Modules\Setting\Commands\Drivers;

use Ramsey\Uuid\UuidInterface;

class UpdateTwilioWhatsAppCommand implements DriverCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $twilioSid,
        private string $twilioAuthToken,
        private string $twilioWhatsAppFrom,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'TWILIO_SID' => $this->twilioSid,
            'TWILIO_AUTH_TOKEN' => $this->twilioAuthToken,
            'TWILIO_WHATSAPP_FROM' => $this->twilioWhatsAppFrom,
        ];
    }
}

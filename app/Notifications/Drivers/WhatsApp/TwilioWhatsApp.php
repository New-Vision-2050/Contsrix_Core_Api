<?php

namespace App\Notifications\Drivers\WhatsApp;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Modules\Setting\Models\Driver;

class TwilioWhatsApp
{
    protected string $to = '';
    protected string $line = '';
    protected string $accountSid;
    protected string $authToken;
    protected string $whatsappFrom;

    public function __construct(string $line = '')
    {
        $this->line = $line;

        try {
            $driver = Driver::query()
                ->where('driver_type', 'whatsapp')
                ->where('name', 'twilio')
                ->first();

            if ($driver && ! empty($driver->config['TWILIO_SID']) && ! empty($driver->config['TWILIO_AUTH_TOKEN'])) {
                $this->accountSid = $driver->config['TWILIO_SID'];
                $this->authToken = $driver->config['TWILIO_AUTH_TOKEN'];
                $this->whatsappFrom = $driver->config['TWILIO_WHATSAPP_FROM'] ?? '';

                return;
            }
        } catch (\Throwable $e) {
            Log::warning('TwilioWhatsApp: could not query drivers table, falling back to env config', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->accountSid = config('services.twilio.sid', '');
        $this->authToken = config('services.twilio.auth_token', '');
        $this->whatsappFrom = config('services.twilio.whatsapp_from', '');
    }

    public function line(string $line = ''): self
    {
        $this->line = $line;

        return $this;
    }

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function from(string $from): self
    {
        $this->whatsappFrom = $from;

        return $this;
    }

    public function send(): mixed
    {
        if (empty($this->accountSid) || empty($this->authToken) || empty($this->whatsappFrom)) {
            Log::error('Twilio WhatsApp is not configured. SID, Auth Token, and WhatsApp From are required.', [
                'to' => $this->to,
            ]);

            return false;
        }

        if (empty($this->to)) {
            Log::error('Twilio WhatsApp recipient is missing.');

            return false;
        }

        $to = $this->normalizeWhatsAppNumber($this->to);
        $from = $this->normalizeWhatsAppNumber($this->whatsappFrom);

        try {
            $client = new Client($this->accountSid, $this->authToken);

            $message = $client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $this->line,
                ]
            );

            Log::info('Twilio WhatsApp message sent', [
                'sid' => $message->sid,
                'to' => $to,
                'from' => $from,
                'status' => $message->status,
            ]);

            return $message->sid;
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp message failed', [
                'to' => $to,
                'from' => $from,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizeWhatsAppNumber(string $number): string
    {
        $number = trim($number);

        if (! str_starts_with($number, '+')) {
            $number = '+' . ltrim($number, '+');
        }

        if (! str_starts_with($number, 'whatsapp:')) {
            $number = 'whatsapp:' . $number;
        }

        return $number;
    }
}

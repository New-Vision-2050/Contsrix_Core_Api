<?php

namespace App\Notifications\Drivers\Voice;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Modules\Setting\Models\Driver;

class TwilioVoice
{
    protected string $to = '';
    protected string $from = '';
    protected string $url = '';
    protected string $accountSid;
    protected string $authToken;
    protected string $apiKeySid;
    protected string $apiKeySecret;

    public function __construct()
    {
        $this->accountSid = config('services.twilio_voice.sid', '');
        $this->authToken = config('services.twilio.auth_token', '');
        $this->apiKeySid = config('services.twilio_voice.api_key_sid', '');
        $this->apiKeySecret = config('services.twilio_voice.api_key_secret', '');
        $this->from = config('services.twilio_voice.from', '');

        if (empty($this->accountSid) || (empty($this->authToken) && (empty($this->apiKeySid) || empty($this->apiKeySecret)))) {
            try {
                $driver = Driver::query()
                    ->where('driver_type', 'voice')
                    ->where('name', 'twilio')
                    ->first();

                if ($driver && ! empty($driver->config['TWILIO_SID'])) {
                    if (empty($this->accountSid)) {
                        $this->accountSid = $driver->config['TWILIO_SID'];
                    }
                    if (empty($this->authToken)) {
                        $this->authToken = $driver->config['TWILIO_AUTH_TOKEN'] ?? '';
                    }
                    if (empty($this->apiKeySid)) {
                        $this->apiKeySid = $driver->config['TWILIO_VOICE_API_KEY_SID'] ?? '';
                    }
                    if (empty($this->apiKeySecret)) {
                        $this->apiKeySecret = $driver->config['TWILIO_VOICE_API_KEY_SECRET'] ?? '';
                    }
                    if (empty($this->from)) {
                        $this->from = $driver->config['TWILIO_VOICE_FROM'] ?? '';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('TwilioVoice: could not query drivers table', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function to(string $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function from(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function twimlUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function send(): mixed
    {
        $missingAuth = empty($this->authToken) && (empty($this->apiKeySid) || empty($this->apiKeySecret));
        if (empty($this->accountSid) || $missingAuth || empty($this->from) || empty($this->to)) {
            Log::error('Twilio Voice is not configured. Account SID, From, To, and either Auth Token or API Key are required.');
            return false;
        }

        if (empty($this->url)) {
            Log::error('Twilio Voice TwiML URL is required.');
            return false;
        }

        try {
            $client = $this->createTwilioClient();

            $call = $client->calls->create(
                $this->to,
                $this->from,
                [
                    'url' => $this->url,
                ]
            );

            Log::info('Twilio Voice call initiated', [
                'sid' => $call->sid,
                'to' => $this->to,
                'from' => $this->from,
                'status' => $call->status,
            ]);

            return $call->sid;
        } catch (\Throwable $e) {
            Log::error('Twilio Voice call failed', [
                'to' => $this->to,
                'from' => $this->from,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function createTwilioClient(): Client
    {
        if (! empty($this->apiKeySid) && ! empty($this->apiKeySecret)) {
            return new Client($this->apiKeySid, $this->apiKeySecret, $this->accountSid);
        }

        return new Client($this->accountSid, $this->authToken);
    }
}

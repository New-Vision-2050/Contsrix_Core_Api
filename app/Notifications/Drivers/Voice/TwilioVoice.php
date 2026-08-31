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
    protected string $twiml = '';
    protected string $accountSid;
    protected string $authToken;
    protected string $apiKeySid;
    protected string $apiKeySecret;

    public function __construct()
    {
        $this->accountSid = (string) config('services.twilio_voice.sid', '');
        $this->authToken = (string) config('services.twilio.auth_token', '');
        $this->apiKeySid = (string) config('services.twilio_voice.api_key_sid', '');
        $this->apiKeySecret = (string) config('services.twilio_voice.api_key_secret', '');
        $this->from = (string) config('services.twilio_voice.from', '');

        if ($this->needsDriverHydration()) {
            $this->hydrateFromDrivers();
        }

        $this->from = $this->normalizePhone($this->from);
    }

    public function to(string $to): self
    {
        $this->to = $this->normalizePhone($to);

        return $this;
    }

    public function from(string $from): self
    {
        $this->from = $this->normalizePhone($from);

        return $this;
    }

    public function twimlUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function twiml(string $twiml): self
    {
        $this->twiml = $twiml;
        return $this;
    }

    public function send(): mixed
    {
        $missingAuth = empty($this->authToken) && (empty($this->apiKeySid) || empty($this->apiKeySecret));
        if (empty($this->accountSid) || $missingAuth || empty($this->from) || empty($this->to)) {
            Log::error('Twilio Voice is not configured. Account SID, From, To, and either Auth Token or API Key are required.', [
                'has_sid' => $this->accountSid !== '',
                'has_auth_token' => $this->authToken !== '',
                'has_api_key' => $this->apiKeySid !== '' && $this->apiKeySecret !== '',
                'has_from' => $this->from !== '',
                'has_to' => $this->to !== '',
                'from' => $this->from,
                'to' => $this->to,
            ]);

            return false;
        }

        if (empty($this->url) && empty($this->twiml)) {
            Log::error('Twilio Voice requires either a TwiML URL or raw TwiML.');
            return false;
        }

        try {
            $client = $this->createTwilioClient();

            $params = [];
            if (! empty($this->twiml)) {
                $params['twiml'] = $this->twiml;
            } else {
                $params['url'] = $this->url;
            }

            $call = $client->calls->create(
                $this->to,
                $this->from,
                $params
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

    private function needsDriverHydration(): bool
    {
        $missingAuth = empty($this->authToken) && (empty($this->apiKeySid) || empty($this->apiKeySecret));

        return empty($this->accountSid) || $missingAuth || empty($this->from) || str_starts_with(strtolower($this->from), 'whatsapp:');
    }

    private function hydrateFromDrivers(): void
    {
        try {
            $drivers = Driver::query()
                ->where('name', 'twilio')
                ->whereIn('driver_type', ['voice', 'whatsapp'])
                ->get();

            foreach ($drivers->sortBy(fn (Driver $driver) => $driver->driver_type === 'voice' ? 0 : 1) as $driver) {
                $config = $driver->config ?? [];
                if (! is_array($config) || $config === []) {
                    continue;
                }

                if (empty($this->accountSid) && ! empty($config['TWILIO_SID'])) {
                    $this->accountSid = (string) $config['TWILIO_SID'];
                }
                if (empty($this->authToken) && ! empty($config['TWILIO_AUTH_TOKEN'])) {
                    $this->authToken = (string) $config['TWILIO_AUTH_TOKEN'];
                }
                if (empty($this->apiKeySid) && ! empty($config['TWILIO_VOICE_API_KEY_SID'])) {
                    $this->apiKeySid = (string) $config['TWILIO_VOICE_API_KEY_SID'];
                }
                if (empty($this->apiKeySecret) && ! empty($config['TWILIO_VOICE_API_KEY_SECRET'])) {
                    $this->apiKeySecret = (string) $config['TWILIO_VOICE_API_KEY_SECRET'];
                }
                if (empty($this->from) || str_starts_with(strtolower($this->from), 'whatsapp:')) {
                    $voiceFrom = (string) ($config['TWILIO_VOICE_FROM'] ?? '');
                    $whatsappFrom = (string) ($config['TWILIO_WHATSAPP_FROM'] ?? '');
                    if ($voiceFrom !== '') {
                        $this->from = $voiceFrom;
                    } elseif ($this->from === '' && $whatsappFrom !== '') {
                        $this->from = $whatsappFrom;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TwilioVoice: could not query drivers table', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(string $number): string
    {
        $number = trim($number);
        if (str_starts_with(strtolower($number), 'whatsapp:')) {
            $number = substr($number, strlen('whatsapp:'));
        }

        return trim($number);
    }
}

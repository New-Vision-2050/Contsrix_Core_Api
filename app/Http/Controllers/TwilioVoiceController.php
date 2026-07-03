<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Notifications\Drivers\Voice\TwilioVoice;

class TwilioVoiceController
{
    /**
     * Handle incoming voice calls from Twilio.
     */
    public function handleIncoming(Request $request): Response
    {
        $response = <<<TWIML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="Polly.Zeina" language="ar-EG">مرحباً، أهلاً بك في كونستريكس.</Say>
</Response>
TWIML;

        return response($response, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Trigger an outbound voice call.
     */
    public function call(Request $request): array
    {
        $validated = $request->validate([
            'to' => 'required|string',
            'from' => 'sometimes|string',
            'twiml_url' => 'sometimes|url',
        ]);

        $to = $validated['to'];
        $from = $validated['from'] ?? config('services.twilio_voice.from');
        $twimlUrl = $validated['twiml_url'] ?? url('/api/twilio/voice');

        $sid = (new TwilioVoice)
            ->to($to)
            ->from($from)
            ->twimlUrl($twimlUrl)
            ->send();

        return [
            'success' => (bool) $sid,
            'call_sid' => $sid,
        ];
    }
}

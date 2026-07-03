<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;
use App\Events\ConnectionTestEvent;
use App\Http\Controllers\TwilioVoiceController;

Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'constrix-api'
    ]);
});

// WebSocket connection test — no auth, no tenancy required
Route::get('/test-broadcast', function () {
    event(new ConnectionTestEvent('Hello from Reverb! Connection is working.'));
    return response()->json(['status' => 'event fired', 'timestamp' => now()]);
});

// Get user companies by email - No tenant initialization required

// Twilio voice webhook (public, no auth — Twilio calls this)
Route::post('/twilio/voice', [TwilioVoiceController::class, 'handleIncoming']);

// Outbound voice call (add your auth middleware if needed)
Route::post('/api/twilio/voice/call', [TwilioVoiceController::class, 'call']);

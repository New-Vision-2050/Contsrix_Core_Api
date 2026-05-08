<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ConnectionTestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $message;
    public string $timestamp;

    public function __construct(string $message = 'Connection test successful!')
    {
        $this->message = $message;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('connection-test'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConnectionTestEvent';
    }
}

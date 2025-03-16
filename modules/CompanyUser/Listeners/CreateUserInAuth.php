<?php

namespace Modules\CompanyUser\Listeners;

use Modules\CompanyUser\Events\CompanyUser;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CompanyUser\Events\UserCreated;
use Modules\User\Models\User;
use RabbitMQ\Jobs\BroadcastMessage;

class CreateUserInAuth
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UserCreated $event
     * @return void
     */
    public function handle(UserCreated $event)
    {
        BroadcastMessage::broadcastToExchange("created_user",$event->data,"user_events_exchange");

    }
}

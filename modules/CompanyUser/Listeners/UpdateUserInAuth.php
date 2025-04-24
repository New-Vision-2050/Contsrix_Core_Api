<?php

namespace Modules\CompanyUser\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Events\UserUpdated;
use Modules\User\Models\User;
use RabbitMQ\Jobs\BroadcastMessage;

class UpdateUserInAuth
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
    public function handle(UserUpdated $event)
    {
        BroadcastMessage::broadcastToExchange("updated_user",$event->data,"user_events_exchange");

    }
}

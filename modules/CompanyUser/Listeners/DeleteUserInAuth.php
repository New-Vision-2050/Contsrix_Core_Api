<?php

namespace Modules\CompanyUser\Listeners;


use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Events\UserDeleted;
use RabbitMQ\Jobs\BroadcastMessage;

class DeleteUserInAuth
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
    public function handle(UserDeleted $event)
    {
        BroadcastMessage::broadcastToExchange("deleted_user",$event->data,"user_events_exchange");

    }
}

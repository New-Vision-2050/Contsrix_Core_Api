<?php

namespace Modules\CompanyUser\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Events\UserDeleted;
use Modules\CompanyUser\Events\UserUpdated;
use Modules\User\Models\User;
use RabbitMQ\Jobs\BroadcastMessage;

class DeleteUserRoleInAuthListener
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
        BroadcastMessage::broadcastToExchange("deleted_user_role",$event->data,"user_events_exchange");

    }
}

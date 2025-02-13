<?php

namespace Modules\CompanyUser\Jobs;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CompanyUserCreated implements ShouldBroadcast
{
    use  SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $data ;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }

    public function broadcastOn()
    {
        return new Channel("company-user-created");
    }
}

<?php

namespace Modules\Company\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Company\Models\Company;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckCompanyActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $company_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($company_id)
    {
        $this->company_id = $company_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $company = Company::whereId($this->company_id)->where('check_activity',0)->first();

        if (!$company) {
            return;
        }

        $company->delete();
        Log::warning("Company ID: {$company->id} has no activity within 24 hours.");

    }
}

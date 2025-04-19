<?php

namespace Modules\Company\CompanyCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Company\CompanyCore\Services\CompanyCheckActivityService;

class CheckCompanyActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $company_id;
    private $companyCheckActivityService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($company_id,)
    {
        $companyCheckActivityService = app()->make(CompanyCheckActivityService::class);

        $this->company_id = $company_id;
        $this->companyCheckActivityService = $companyCheckActivityService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->companyCheckActivityService->handle($this->company_id);
    }
}

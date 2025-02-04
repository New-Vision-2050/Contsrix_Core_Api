<?php

namespace Modules\Company\CompanyCore\Console;

use Illuminate\Console\Command;
use Modules\Company\CompanyCore\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class CheckCompanyActivityCommand extends Command
{
    protected $signature = 'companies:delete-inactive';

    protected $description = 'Deletes inactive companies older than 24 hours';

    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        $inactiveTime = Carbon::now()->subHours(24);

        // Get companies that are inactive and older than 24 hours
        $companies = Company::where('check_activity', 0)
                            ->where('created_at', '<', $inactiveTime)
                            ->get();

        if ($companies->isEmpty()) {
            $this->info("No inactive companies found.");
            return;
        }

        // Iterate over the companies and delete them
        foreach ($companies as $company) {
            $company->delete();
            $this->info("Company ID: {$company->id} has no activity within 24 hours.");
        }
    }

}

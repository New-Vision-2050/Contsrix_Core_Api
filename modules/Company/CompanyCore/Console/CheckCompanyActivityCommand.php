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

        $company = Company::where('check_activity', 0)->where('created_at', '<', $inactiveTime)->first();

        if (!$company) {
            Log::warning("No inactive companies found.");
            $this->info("No inactive companies found.");

            return;
        }

        $company->delete();
        Log::warning("Company ID: {$company->id} has no activity within 24 hours.");
        Log::info("Company ID: {$company->id} has no activity within 24 hours.");

    }

}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Modules\Attendance\Services\AutoAttendanceService;
use Modules\Company\CompanyCore\Models\Company;

class CreateWaitingAttendanceCommand extends Command
{
    protected $signature = 'attendance:create-waiting {--date= : Optional date in Y-m-d format to process (defaults to today)}';
    protected $description = 'Creates waiting attendance records for users who are expected to work today';

    private AutoAttendanceService $autoAttendanceService;

    public function __construct(AutoAttendanceService $autoAttendanceService)
    {
        parent::__construct();
        $this->autoAttendanceService = $autoAttendanceService;
    }

    public function handle()
    {
        $companies = Company::get();
        foreach ($companies as $company) {
            $companyId = $company->id;
            $this->info("Processing company: {$company->name} ({$companyId})");
            $this->autoAttendanceService->generateAttendanceUsers($companyId);
        }
    }


}

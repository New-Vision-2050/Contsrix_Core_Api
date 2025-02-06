<?php

namespace Modules\Company\CompanyCore\Console;

use Illuminate\Console\Command;
use Modules\Company\CompanyCore\Services\CompanyCheckActivityService;

class CheckCompanyActivityCommand extends Command
{
    protected $signature = 'companies:delete-inactive';

    protected $description = 'Deletes inactive companies older than 24 hours';
    private $companyCheckActivityService;
    public function __construct()
    {
        parent::__construct();
        $companyCheckActivityService = new CompanyCheckActivityService;
        $this->companyCheckActivityService = $companyCheckActivityService;
    }
    public function handle()
    {
        $this->companyCheckActivityService->handle();
    }

}

<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use App\Exceptions\CustomException;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Events\CompaniesDeleted;
use Modules\Company\CompanyCore\Models\Company;
use Illuminate\Support\Facades\Log;

class CompanyCheckActivityService
{

    public function handle($companyId = null)
    {
        try {
            $inactiveTime = Carbon::now()->subHours(24);

            $companyIds = Company::where('check_activity', 0)
                ->where('created_at', '<', $inactiveTime)
                ->when($companyId, function ($q) use ($companyId) {
                    $q->where('id', $companyId);
                })
                ->pluck('id');

            if ($companyIds->isEmpty()) {
                return;
            }

            Company::whereIn('id', $companyIds)->delete();

            if ($companyIds->isNotEmpty()) {
                event(new CompaniesDeleted($companyIds));
            }
        } catch (CustomException $e) {
            Log::error('CompanyException: ' . $e->getMessage(), ['errors' => $e->getStatusCode()]);
            throw new CustomException('Failed to delete inactive companies.', 423);
        }
    }

}

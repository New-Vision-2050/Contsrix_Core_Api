<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyField\Repositories\CompanyFieldRepository;
use Modules\SubscriptionSystem\ProgramSystem\DTO\CreateProgramSystemDTO;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;
use Modules\SubscriptionSystem\ProgramSystem\Repositories\ProgramSystemRepository;
use PhpParser\Node\Stmt\Return_;
use Ramsey\Uuid\UuidInterface;

class ProgramSystemWidgetService
{
    public function __construct(
        private ProgramSystemRepository $repository,
        private CompanyFieldRepository $companyFieldRepository
    ) {
    }
    public function widget()
    {
        $programStats = $this->repository->countStatistics();

        $usedFieldsCount = $this->companyFieldRepository->countFieldsUsedInPrograms();

        return [
            'total_programs' => $programStats['total'],
            'active_programs' => $programStats['active'],
            'company_fields_in_use' => $usedFieldsCount,
        ];
    }
}

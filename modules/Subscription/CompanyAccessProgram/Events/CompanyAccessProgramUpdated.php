<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

class CompanyAccessProgramUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CompanyAccessProgram $companyAccessProgram
     * @param array $originalData Original data before update
     */
    public function __construct(
        public CompanyAccessProgram $companyAccessProgram,
        public array $originalData = []
    ) {
    }
}

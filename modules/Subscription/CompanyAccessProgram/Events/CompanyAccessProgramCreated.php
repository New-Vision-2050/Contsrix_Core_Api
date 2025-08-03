<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

class CompanyAccessProgramCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CompanyAccessProgram $companyAccessProgram
     */
    public function __construct(
        public CompanyAccessProgram $companyAccessProgram
    ) {
    }
}

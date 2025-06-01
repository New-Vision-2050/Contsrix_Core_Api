<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;

class CompanyOfficialDocumentUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var CompanyOfficialDocument
     */
    public CompanyOfficialDocument $document;


    public function __construct(CompanyOfficialDocument $document)
    {
        $this->document = $document;
    }
}

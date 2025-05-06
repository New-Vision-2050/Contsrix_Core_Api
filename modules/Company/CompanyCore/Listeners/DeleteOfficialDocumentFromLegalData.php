<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Listeners;

use Modules\Company\CompanyCore\Events\CompanyLegalDataDeleted;

class DeleteOfficialDocumentFromLegalData
{
    /**
     * Handle the event.
     *
     * @param CompanyLegalDataDeleted $event
     * @return void
     */
    public function handle(CompanyLegalDataDeleted $event): void
    {
        $legalData = $event->legalData;
        
        // Delete the associated official document
        if ($legalData->officialDocument) {
            $legalData->officialDocument()->delete();
        }
    }
}
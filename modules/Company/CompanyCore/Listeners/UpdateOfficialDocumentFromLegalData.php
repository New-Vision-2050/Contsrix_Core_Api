<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Company\CompanyCore\Events\CompanyLegalDataUpdated;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;

class UpdateOfficialDocumentFromLegalData
{
    /**
     * Handle the event.
     *
     * @param CompanyLegalDataUpdated $event
     * @return void
     */
    public function handle(CompanyLegalDataUpdated $event): void
    {
        $legalData = $event->legalData;
        $officialDocument = $legalData->officialDocument;

        if ($officialDocument) {
            $officialDocument->update([
                'name' => $legalData->registrationType->name,
                'document_type_id' => $legalData->registration_type_id,
                'document_number' => $legalData->registration_number,
                'start_date' => $legalData->start_date,
                'end_date' => $legalData->end_date,
                'company_legal_data_id' => $legalData->id,
                'company_id' => $legalData->company_id,
                'notification_date' => Carbon::parse($legalData->end_date)->subDays(7),
            ]);

            // Sync media
            $this->syncMediaToDocument($legalData, $officialDocument);
        } else {
            $this->createOfficialDocument($legalData);
        }
    }

    /**
     * Create a new official document from legal data.
     *
     * @param CompanyLegalData $legalData
     * @return void
     */
    protected function createOfficialDocument(CompanyLegalData $legalData): void
    {
        $officialDocument = CompanyOfficialDocument::create([
            'name' => $legalData->registrationType->name,
            'description' => '-',
            'document_type_id' => $legalData->registration_type_id,
            'document_number' => $legalData->registration_number,
            'start_date' => $legalData->start_date,
            'end_date' => $legalData->end_date,
            'notification_date' => Carbon::parse($legalData->end_date)->subDays(7),
            'company_legal_data_id' => $legalData->id,
            'company_id' => $legalData->company_id,
            'management_hierarchy_id' => $legalData->management_hierarchy_id,
        ]);

        $this->syncMediaToDocument($legalData, $officialDocument);
    }

    /**
     * Sync media from legal data to official document.
     *
     * @param CompanyLegalData $legalData
     * @param CompanyOfficialDocument $officialDocument
     * @return void
     */
    protected function syncMediaToDocument(CompanyLegalData $legalData, CompanyOfficialDocument $officialDocument): void
    {
        $legalData = $legalData->refresh();
        
        // Get all media for the legal data (in case of multiple)
        $mediaItems = Media::where('model_id', Uuid::fromString($legalData->id))
            ->where('model_type', CompanyLegalData::class)
            ->get();

        Media::where('model_id', Uuid::fromString($officialDocument->id))
            ->where('model_type', CompanyOfficialDocument::class)
            ->delete();

        /** @var Media $mediaItem */
        foreach ($mediaItems as $mediaItem) {
            // Replicate the media item for the official document
            $item = $mediaItem->replicate(['id', 'uuid']); // local fallback
            $item->model_id = $officialDocument->id;
            $item->model_type = CompanyOfficialDocument::class;
            $item->save();
        }
    }
}
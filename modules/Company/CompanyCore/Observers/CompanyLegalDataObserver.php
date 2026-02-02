<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Observers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Ramsey\Uuid\Uuid;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;

class CompanyLegalDataObserver
{
    public function created(CompanyLegalData $legalData): void
    {
        $this->createOfficialDocument($legalData);
    }

    public function updated(CompanyLegalData $legalData): void
    {
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
                'notification_date' => \Carbon\Carbon::parse($legalData->end_date)->subDays(7),
            ]);

            // Optionally sync media
            $this->syncMediaToDocument($legalData, $officialDocument);
        } else {
            $this->createOfficialDocument($legalData);
        }
    }

    public function deleted(CompanyLegalData $legalData): void
    {
        $legalData->officialDocument()->delete();
    }

    protected function createOfficialDocument(CompanyLegalData $legalData): void
    {
        $officialDocument = CompanyOfficialDocument::create([
            'name' => $legalData->registrationType->name,
            'description' => '-',
            'document_type_id' => $legalData->registration_type_id,
            'document_number' => $legalData->registration_number,
            'start_date' => $legalData->start_date,
            'end_date' => $legalData->end_date,
            'notification_date' => \Carbon\Carbon::parse($legalData->end_date)->subDays(7),
            'company_legal_data_id' => $legalData->id,
            'company_id' => $legalData->company_id,
            'management_hierarchy_id' => $legalData->management_hierarchy_id,
        ]);

        $this->syncMediaToDocument($legalData, $officialDocument);
    }

    protected function syncMediaToDocument(CompanyLegalData $legalData, CompanyOfficialDocument $officialDocument): void
    {
        $legalData = $legalData->refresh();
        // Clear existing media on the official document
        //$officialDocument->clearMediaCollection('official_document');

        // Get all media for the legal data (in case of multiple)
        $mediaItems = Media::where('model_id', Uuid::fromString($legalData->id))
            ->where('model_type', CompanyLegalData::class)
            ->get();

        Media::where('model_id', Uuid::fromString($officialDocument->id))
            ->where('model_type', CompanyOfficialDocument::class)
            ->delete();

        /** @var Media $mediaItem $mediaItem */
        foreach ($mediaItems as $mediaItem) {

            // Get the full S3 URL or relative path
            $item = $mediaItem->replicate(['id','uuid']); // local fallback
            $item->model_id = $officialDocument->id;
            $item->model_type= CompanyOfficialDocument::class;
            $item->save();
        }
    }
}

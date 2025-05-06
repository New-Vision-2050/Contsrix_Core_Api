<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Observers;

use Illuminate\Support\Str;
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
            'name' => 'Auto-generated Document',
            'document_type_id' => $legalData->registration_type_id,
            'description' => 'Auto created from Legal Data',
            'document_number' => $legalData->registration_number,
            'start_date' => $legalData->start_date,
            'end_date' => $legalData->end_date,
            'notification_date' => now()->addDays(7),
            'company_legal_data_id' => $legalData->id,
            'company_id' => $legalData->company_id,
            'management_hierarchy_id' => $legalData->management_hierarchy_id,
        ]);

        $this->syncMediaToDocument($legalData, $officialDocument);
    }

    protected function syncMediaToDocument(CompanyLegalData $legalData, CompanyOfficialDocument $officialDocument): void
    {
        $originalMedia = Media::where('model_id', Uuid::fromString($legalData->id))
            ->where('model_type', CompanyLegalData::class)
            ->first();

        if ($originalMedia) {
            Media::create([
                'model_id' => $officialDocument->id,
                'model_type' => CompanyOfficialDocument::class,
                'uuid' => Str::uuid(),
                'collection_name' => 'official_document',
                'name' => $originalMedia->name,
                'file_name' => $originalMedia->file_name,
                'mime_type' => $originalMedia->mime_type,
                'disk' => $originalMedia->disk,
                'conversions_disk' => $originalMedia->conversions_disk,
                'size' => $originalMedia->size,
                'manipulations' => $originalMedia->manipulations,
                'custom_properties' => $originalMedia->custom_properties,
                'responsive_images' => $originalMedia->responsive_images,
                'order_column' => $originalMedia->order_column,
                'generated_conversions' => $originalMedia->generated_conversions,
            ]);
        }
    }
}

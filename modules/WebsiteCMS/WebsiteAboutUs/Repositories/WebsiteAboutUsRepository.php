<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUs;
use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUsProjectType;
use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUsAttachment;
use App\Traits\HasExport;

/**
 * @property WebsiteAboutUs $model
 * @method WebsiteAboutUs findOneOrFail($id)
 * @method WebsiteAboutUs findOneByOrFail(array $data)
 */
class WebsiteAboutUsRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        WebsiteAboutUs $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getWebsiteAboutUsList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteAboutUs(UuidInterface $id): WebsiteAboutUs
    {
        return $this->model
            ->with(['projectTypes', 'attachments'])
            ->findOrFail($id->toString());
    }

    public function getCurrentCompanyAboutUs(): ?WebsiteAboutUs
    {
        return $this->model
            ->with(['projectTypes', 'attachments', 'certificateIcons', 'approvalIcons', 'companyIcons'])
            ->where('company_id', tenant('id'))
            ->first();
    }

    public function createWebsiteAboutUs(
        array $data,
        ?UploadedFile $mainImage = null,
        ?array $projectTypes = null,
        ?array $attachments = null
    ): WebsiteAboutUs {
        return DB::transaction(function () use ($data, $mainImage, $projectTypes, $attachments) {
            $data['company_id'] = tenant('id');
            $websiteAboutUs = $this->create($data);

            // Upload main image
            if ($mainImage) {
                $this->fileUploadService->uploadFile(
                    $websiteAboutUs,
                    $mainImage,
                    'website-about-us/main-image',
                    'main_image',
                    'public'
                );
            }

            // Create project types
            if ($projectTypes) {
                foreach ($projectTypes as $projectType) {
                    $websiteAboutUs->projectTypes()->create([
                        'title' => [
                            'ar' => $projectType['title_ar'],
                            'en' => $projectType['title_en'],
                        ],
                        'count' => $projectType['count'],
                    ]);
                }
            }

            // Create attachments
            if ($attachments) {
                foreach ($attachments as $index => $attachmentData) {
                    $attachment = $websiteAboutUs->attachments()->create([
                        'name' => $attachmentData['name'],
                    ]);

                    // Upload attachment file
                    if (isset($attachmentData['attachment'])) {
                        $this->fileUploadService->uploadFile(
                            $attachment,
                            $attachmentData['attachment'],
                            'website-about-us/attachments',
                            'attachment',
                            'public'
                        );
                    }
                }
            }

            return $websiteAboutUs->fresh(['projectTypes', 'attachments']);
        });
    }

    public function updateCurrentCompanyAboutUs(
        array $data,
        ?UploadedFile $mainImage = null,
        ?array $projectTypes = null,
        ?array $attachments = null
    ): WebsiteAboutUs {
        return DB::transaction(function () use ($data, $mainImage, $projectTypes, $attachments) {
            $websiteAboutUs = $this->getCurrentCompanyAboutUs();

            if (!$websiteAboutUs) {
                // Create if doesn't exist
                return $this->createWebsiteAboutUs($data, $mainImage, $projectTypes, $attachments);
            }

            $websiteAboutUs->update($data);

            // Update main image if provided
            if ($mainImage) {
                $this->fileUploadService->uploadFile(
                    $websiteAboutUs,
                    $mainImage,
                    'website-about-us/main-image',
                    'main_image',
                    'public'
                );
            }

            // Update project types - delete old and create new
            if ($projectTypes !== null) {
                $websiteAboutUs->projectTypes()->delete();
                foreach ($projectTypes as $projectType) {
                    WebsiteAboutUsProjectType::create([
                        "website_about_us_id"=>$websiteAboutUs->id,
                        'title' => [
                            'ar' => $projectType['title_ar'],
                            'en' => $projectType['title_en'],
                        ],
                        'count' => $projectType['count'],
                    ]);
                }
            }

            // Update attachments with smart sync logic
            if ($attachments !== null) {
                // Collect incoming attachment IDs
                $incomingIds = collect($attachments)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete attachments not in incoming data
                $attachmentsToDelete = $websiteAboutUs->attachments()
                    ->whereNotIn('id', $incomingIds)
                    ->get();

                foreach ($attachmentsToDelete as $oldAttachment) {
                    $oldAttachment->clearMediaCollection('attachment');
                    $oldAttachment->delete();
                }

                // Process each attachment
                foreach ($attachments as $attachmentData) {

                    if ($attachmentData['id'] != null && $attachmentData['id'] != "null") {
                        // Update existing attachment
                        $attachment = WebsiteAboutUsAttachment::find($attachmentData['id']);

                        if ($attachment) {
                            $attachment->update([
                                'name' => $attachmentData['name'],
                            ]);

                            // Update attachment file if provided
                            if (isset($attachmentData['attachment'])) {
                                $attachment->clearMediaCollection('attachment');
                                $this->fileUploadService->uploadFile(
                                    $attachment,
                                    $attachmentData['attachment'],
                                    'website-about-us/attachments',
                                    'attachment',
                                    'public'
                                );
                            }
                        }
                    } else {

                        // Create new attachment (id is null)
                        $attachment = $websiteAboutUs->attachments()->create([
                            'name' => $attachmentData['name'],
                        ]);

                        // Upload attachment file
                        if (isset($attachmentData['attachment'])) {
                            $this->fileUploadService->uploadFile(
                                $attachment,
                                $attachmentData['attachment'],
                                'website-about-us/attachments',
                                'attachment',
                                'public'
                            );
                        }
                    }
                }
            }

            return $websiteAboutUs->fresh(['projectTypes', 'attachments']);
        });
    }

    public function updateWebsiteAboutUs(
        UuidInterface $id,
        array $data,
        ?UploadedFile $mainImage = null,
        ?array $projectTypes = null,
        ?array $attachments = null
    ): WebsiteAboutUs {
        return DB::transaction(function () use ($id, $data, $mainImage, $projectTypes, $attachments) {
            $websiteAboutUs = $this->findOneOrFail($id);
            $websiteAboutUs->update($data);

            // Update main image if provided
            if ($mainImage) {
                $websiteAboutUs->clearMediaCollection('main_image');
                $this->fileUploadService->uploadFile(
                    $websiteAboutUs,
                    $mainImage,
                    'website-about-us/main-image',
                    'main_image',
                    'public'
                );
            }

            // Update project types - delete old and create new
            if ($projectTypes !== null) {
                $websiteAboutUs->projectTypes()->delete();
                foreach ($projectTypes as $projectType) {
                    $websiteAboutUs->projectTypes()->create([
                        'title' => [
                            'ar' => $projectType['title_ar'],
                            'en' => $projectType['title_en'],
                        ],
                        'count' => $projectType['count'],
                    ]);
                }
            }

            // Update attachments - delete old and create new
            if ($attachments !== null) {
                // Delete old attachments and their files
                foreach ($websiteAboutUs->attachments as $oldAttachment) {
                    $oldAttachment->clearMediaCollection('attachment');
                    $oldAttachment->delete();
                }

                // Create new attachments
                foreach ($attachments as $attachmentData) {
                    $attachment = $websiteAboutUs->attachments()->create([
                        'name' => $attachmentData['name'],
                    ]);

                    // Upload attachment file
                    if (isset($attachmentData['attachment'])) {
                        $this->fileUploadService->uploadFile(
                            $attachment,
                            $attachmentData['attachment'],
                            'website-about-us/attachments',
                            'attachment',
                            'public'
                        );
                    }
                }
            }

            return $websiteAboutUs->fresh(['projectTypes', 'attachments']);
        });
    }

    public function deleteWebsiteAboutUs(UuidInterface $id): bool
    {
        return DB::transaction(function () use ($id) {
            $websiteAboutUs = $this->findOneOrFail($id);

            // Delete all attachments and their files
            foreach ($websiteAboutUs->attachments as $attachment) {
                $attachment->clearMediaCollection('attachment');
                $attachment->delete();
            }

            // Delete project types
            $websiteAboutUs->projectTypes()->delete();

            // Clear main image
            $websiteAboutUs->clearMediaCollection('main_image');

            // Delete the main record
            return $websiteAboutUs->delete();
        });
    }
}

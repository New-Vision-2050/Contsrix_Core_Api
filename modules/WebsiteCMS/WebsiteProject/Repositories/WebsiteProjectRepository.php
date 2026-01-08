<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProject;
use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProjectDetail;
use Modules\Shared\Media\Services\FileUploadService;
use App\Traits\HasExport;
use Illuminate\Support\Facades\DB;

/**
 * @property WebsiteProject $model
 * @method WebsiteProject findOneOrFail($id)
 * @method WebsiteProject findOneByOrFail(array $data)
 */
class WebsiteProjectRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        WebsiteProject $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getWebsiteProjectList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteProject(UuidInterface $id): WebsiteProject
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteProject(
        array $data,
        ?UploadedFile $mainImage = null,
        array $secondaryImages = [],
        array $projectDetails = []
    ): WebsiteProject {
        return DB::transaction(function () use ($data, $mainImage, $secondaryImages, $projectDetails) {
            // Create the website project
            $websiteProject = $this->create($data);

            // Upload main image if provided
            if ($mainImage) {
                $this->fileUploadService->uploadFile(
                    $websiteProject,
                    $mainImage,
                    'website-project/main-image',
                    'main_image',
                    'public'
                );
            }

            // Upload secondary images if provided
            if (!empty($secondaryImages)) {
                foreach ($secondaryImages as $secondaryImage) {
                    if ($secondaryImage) {
                        $this->fileUploadService->uploadFile(
                            $websiteProject,
                            $secondaryImage,
                            'website-project/secondary-images',
                            'secondary_images',
                            'public'
                        );
                    }
                }
            }

            // Create project details if provided
            if (!empty($projectDetails)) {
                foreach ($projectDetails as $detail) {
                    WebsiteProjectDetail::create([
                        'website_project_id' => $websiteProject->id,
                        'website_service_id' => $detail['website_service_id'],
                        'name' => [
                            'ar' => $detail['name_ar'],
                            'en' => $detail['name_en'],
                        ],
                    ]);
                }
            }

            return $websiteProject->fresh(['projectDetails', 'services', 'websiteProjectSetting']);
        });
    }

    public function updateWebsiteProject(
        UuidInterface $id,
        array $data,
        ?UploadedFile $mainImage = null,
        array $secondaryImages = [],
        array $projectDetails = []
    ): WebsiteProject {
        return DB::transaction(function () use ($id, $data, $mainImage, $secondaryImages, $projectDetails) {
            // Get the website project
            $websiteProject = $this->findOneOrFail($id);

            // Update the website project
            $websiteProject->update($data);

            // Update main image if provided
            if ($mainImage) {
                $websiteProject->clearMediaCollection('main_image');

                $this->fileUploadService->uploadFile(
                    $websiteProject,
                    $mainImage,
                    'website-project/main-image',
                    'main_image',
                    'public'
                );
            }

            // Update secondary images if provided
            if (!empty($secondaryImages)) {
                // Clear existing secondary images

                foreach ($secondaryImages as $secondaryImage) {
                    if ($secondaryImage) {
                        $this->fileUploadService->uploadFile(
                            $websiteProject,
                            $secondaryImage,
                            'website-project/secondary-images',
                            'secondary_images',
                            'public'
                        );
                    }
                }
            }

            // Update project details if provided
            if (!empty($projectDetails)) {
                // Delete existing project details
                $websiteProject->projectDetails()->delete();

                // Create new project details
                foreach ($projectDetails as $detail) {
                    WebsiteProjectDetail::create([
                        'website_project_id' => $websiteProject->id,
                        'website_service_id' => $detail['website_service_id'],
                        'name' => [
                            'ar' => $detail['name_ar'],
                            'en' => $detail['name_en'],
                        ],
                    ]);
                }
            }

            return $websiteProject->fresh(['projectDetails', 'services', 'websiteProjectSetting']);
        });
    }

    public function deleteWebsiteProject(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getFeaturedProjects(int $limit = null): Collection
    {
        if ($limit == null)
        {
            return $this->model
            ->where('company_id', tenant('id'))
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();
        }
        return $this->model
            ->where('company_id', tenant('id'))
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->with(['projectDetails', 'services', 'websiteProjectSetting',"media"])
            ->get();
    }

    public function deleteMedia(UuidInterface $id, int $mediaId)
    {
        $websiteProject = $this->findOneOrFail($id);

         $websiteProject->deleteMedia($mediaId);
    }
}

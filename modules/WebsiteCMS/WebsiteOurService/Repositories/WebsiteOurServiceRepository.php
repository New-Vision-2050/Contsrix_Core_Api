<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteOurService\Models\WebsiteOurService;
use App\Traits\HasExport;

/**
 * @property WebsiteOurService $model
 * @method WebsiteOurService findOneOrFail($id)
 * @method WebsiteOurService findOneByOrFail(array $data)
 */
class WebsiteOurServiceRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteOurService $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteOurServiceList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['departments.websiteServices'], $page, $perPage);
    }

    public function getWebsiteOurService(UuidInterface $id): WebsiteOurService
    {
        return $this->model
            ->with(['departments.websiteServices'])
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function createWebsiteOurService(array $data, array $departments): WebsiteOurService
    {
        return \DB::transaction(function () use ($data, $departments) {
            $data['company_id'] = tenant('id');
            $websiteOurService = $this->create($data);

            // Create departments with their website services
            foreach ($departments as $departmentData) {
                $websiteServiceIds = $departmentData['website_service_ids'] ?? [];
                unset($departmentData['website_service_ids']);

                $department = $websiteOurService->departments()->create([
                    'title' => [
                        'ar' => $departmentData['title_ar'],
                        'en' => $departmentData['title_en'],
                    ],
                    'description' => [
                        'ar' => $departmentData['description_ar'] ?? null,
                        'en' => $departmentData['description_en'] ?? null,
                    ],
                    'type' => $departmentData['type'],
                ]);

                // Attach website services to department
                if (!empty($websiteServiceIds)) {
                    $department->websiteServices()->attach($websiteServiceIds);
                }
            }

            return $websiteOurService->fresh(['departments.websiteServices']);
        });
    }

    public function updateWebsiteOurService(UuidInterface $id, array $data, array $departments): WebsiteOurService
    {
        return \DB::transaction(function () use ($id, $data, $departments) {
            $websiteOurService = $this->findOneOrFail($id);
            $websiteOurService->update($data);

            // Delete old departments
            $websiteOurService->departments()->delete();

            // Create new departments with their website services
            foreach ($departments as $departmentData) {
                $websiteServiceIds = $departmentData['website_service_ids'] ?? [];
                unset($departmentData['website_service_ids']);

                $department = $websiteOurService->departments()->create([
                    'title' => [
                        'ar' => $departmentData['title_ar'],
                        'en' => $departmentData['title_en'],
                    ],
                    'description' => [
                        'ar' => $departmentData['description_ar'] ?? null,
                        'en' => $departmentData['description_en'] ?? null,
                    ],
                    'type' => $departmentData['type'],
                ]);

                // Attach website services to department
                if (!empty($websiteServiceIds)) {
                    $department->websiteServices()->attach($websiteServiceIds);
                }
            }

            return $websiteOurService->fresh(['departments.websiteServices']);
        });
    }

    public function deleteWebsiteOurService(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getCurrentCompanyWebsiteOurService(): ?WebsiteOurService
    {
        return $this->model
            ->with(['departments.websiteServices'])
            ->where('company_id', tenant('id'))
            ->first();
    }

    public function updateCurrentCompanyWebsiteOurService(array $data, array $departments): WebsiteOurService
    {
        return \DB::transaction(function () use ($data, $departments) {
            $websiteOurService = $this->getCurrentCompanyWebsiteOurService();

            if (!$websiteOurService) {
                // Create new one if doesn't exist
                $data['company_id'] = tenant('id');
                $websiteOurService = $this->create($data);
            } else {
                // Update existing
                $websiteOurService->update($data);
                // Delete old departments
                $websiteOurService->departments()->delete();
            }

            // Create departments with their website services
            foreach ($departments as $departmentData) {
                $websiteServiceIds = $departmentData['website_service_ids'] ?? [];
                unset($departmentData['website_service_ids']);

                $department = $websiteOurService->departments()->create([
                    'title' => [
                        'ar' => $departmentData['title_ar'],
                        'en' => $departmentData['title_en'],
                    ],
                    'description' => [
                        'ar' => $departmentData['description_ar'] ?? null,
                        'en' => $departmentData['description_en'] ?? null,
                    ],
                    'type' => $departmentData['type'],
                ]);

                // Attach website services to department
                if (!empty($websiteServiceIds)) {
                    $department->websiteServices()->attach($websiteServiceIds);
                }
            }

            return $websiteOurService->fresh(['departments.websiteServices']);
        });
    }
}

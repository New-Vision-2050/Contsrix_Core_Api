<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\WebsiteCMS\WebsiteService\Models\PreviousWork;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class PreviousWorkServiceRepository extends BaseRepository
{
    public function __construct(PreviousWork $model,private FileUploadService $fileUploadService)
    {
        parent::__construct($model);
    }

    public function syncPreviousWork(WebsiteService $service, array $previousWorkData)
    {
        $service->previousWorks()->delete();

        // Create new previous works
        foreach ($previousWorkData as $work) {
            $previousWork = PreviousWork::create([
                'description' => $work['description'] ?? null,
                "website_service_id" => $service->id
            ]);

            // Handle image if provided
            if (isset($work['image']) && $work['image']) {


                $this->fileUploadService->uploadFile(
                    $previousWork,
                    $work['image'],
                    'website-service/previous-work/images',
                    'image',
                    'public'
                );
            }
        }
    }








}

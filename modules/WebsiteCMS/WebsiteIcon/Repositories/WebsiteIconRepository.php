<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use Modules\Shared\Media\Services\FileUploadService;
use App\Traits\HasExport;

/**
 * @property WebsiteIcon $model
 * @method WebsiteIcon findOneOrFail($id)
 * @method WebsiteIcon findOneByOrFail(array $data)
 */
class WebsiteIconRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        WebsiteIcon $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getWebsiteIconList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteIcon(UuidInterface $id): WebsiteIcon
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteIcon(array $data, ?UploadedFile $icon = null): WebsiteIcon
    {
        $data['company_id'] = tenant('id');
        $websiteIcon = $this->create($data);

        if ($icon) {
            $this->fileUploadService->uploadFile(
                $websiteIcon,
                $icon,
                'website-icon/icon',
                'icon',
                'public'
            );
        }

        return $websiteIcon->fresh(['category']);
    }

    public function updateWebsiteIcon(UuidInterface $id, array $data, ?UploadedFile $icon = null): WebsiteIcon
    {
        $this->update($id, $data);
        $websiteIcon = $this->find($id);

        if ($icon) {
            $websiteIcon->clearMediaCollection('icon');
            $this->fileUploadService->uploadFile(
                $websiteIcon,
                $icon,
                'website-icon/icon',
                'icon',
                'public'
            );
        }

        return $websiteIcon->load(['category']);
    }

    public function deleteWebsiteIcon(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

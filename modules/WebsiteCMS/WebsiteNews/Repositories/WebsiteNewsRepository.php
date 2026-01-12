<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;
use App\Traits\HasExport;
use Modules\Shared\Media\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

/**
 * @property WebsiteNews $model
 * @method WebsiteNews findOneOrFail($id)
 * @method WebsiteNews findOneByOrFail(array $data)
 */
class WebsiteNewsRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteNews $model, private FileUploadService $fileUploadService)
    {
        parent::__construct($model);
    }

    public function getWebsiteNewsList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteNews(UuidInterface $id): WebsiteNews
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteNews(array $data, UploadedFile $mainImage, UploadedFile $thumbnail): WebsiteNews
    {
        $data['company_id'] = tenant('id');
        $news = $this->create($data);

        $this->fileUploadService->uploadFile(
            $news,
            $mainImage,
            'website-news/main-image',
            'main_image',
            'public'
        );

        $this->fileUploadService->uploadFile(
            $news,
            $thumbnail,
            'website-news/thumbnail',
            'thumbnail',
            'public'
        );

        return $news->fresh(['category']);
    }

    public function updateWebsiteNews(UuidInterface $id, array $data, ?UploadedFile $mainImage = null, ?UploadedFile $thumbnail = null): WebsiteNews
    {
        $this->update($id, $data);
        $news = $this->find($id);

        if ($mainImage) {
            $news->clearMediaCollection('main_image');
            $this->fileUploadService->uploadFile(
                $news,
                $mainImage,
                'website-news/main-image',
                'main_image',
                'public'
            );
        }

        if ($thumbnail) {
            $news->clearMediaCollection('thumbnail');
            $this->fileUploadService->uploadFile(
                $news,
                $thumbnail,
                'website-news/thumbnail',
                'thumbnail',
                'public'
            );
        }

        return $news->fresh(['category']);
    }

    public function deleteWebsiteNews(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function toggleStatus(UuidInterface $id): WebsiteNews
    {
        $news = $this->getWebsiteNews($id);
        $newStatus = $news->status == 1 ? 0 : 1;
        $this->update($id, ['status' => $newStatus]);

        return $news->fresh(['category']);
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\SocialMediaLink\Models\SocialMediaLink;
use App\Traits\HasExport;

/**
 * @property SocialMediaLink $model
 * @method SocialMediaLink findOneOrFail($id)
 * @method SocialMediaLink findOneByOrFail(array $data)
 */
class SocialMediaLinkRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        SocialMediaLink $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getSocialMediaLinkList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSocialMediaLink(UuidInterface $id): SocialMediaLink
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSocialMediaLink(array $data, ?UploadedFile $icon = null): SocialMediaLink
    {
        $data['company_id'] = tenant('id');
        $socialMediaLink = $this->create($data);

        if ($icon) {
            $this->fileUploadService->uploadFile(
                $socialMediaLink,
                $icon,
                'social-media-link/icon',
                'icon',
                'public'
            );
        }

        return $socialMediaLink->fresh();
    }

    public function updateSocialMediaLink(UuidInterface $id, array $data, ?UploadedFile $icon = null): bool
    {
        $socialMediaLink = $this->getSocialMediaLink($id);

        if ($icon) {
            $this->fileUploadService->uploadFile(
                $socialMediaLink,
                $icon,
                'social-media-link/icon',
                'icon',
                'public'
            );
        }

        return $this->update($id, $data);
    }

    public function updateStatus(UuidInterface $id, int $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function deleteSocialMediaLink(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Http\UploadedFile;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Models\WebsiteHomePageSetting;
use App\Traits\HasExport;

/**
 * @property WebsiteHomePageSetting $model
 * @method WebsiteHomePageSetting findOneOrFail($id)
 * @method WebsiteHomePageSetting findOneByOrFail(array $data)
 */
class WebsiteHomePageSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        WebsiteHomePageSetting $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getWebsiteHomePageSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteHomePageSetting(UuidInterface $id): WebsiteHomePageSetting
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function getCurrentCompanySetting(): ?WebsiteHomePageSetting
    {
        return $this->model->where('company_id', tenant('id'))->with("media")->first();
    }

    public function createWebsiteHomePageSetting(
        array $data,
        ?UploadedFile $webVideoFile = null,
        ?UploadedFile $mobileVideoFile = null,
        ?UploadedFile $videoProfileFile = null
    ): WebsiteHomePageSetting {
        $data['company_id'] = tenant('id');

        $setting = $this->create($data);

        if ($webVideoFile) {
            $this->fileUploadService->uploadFile(
                $setting,
                $webVideoFile,
                'website-home-page-setting/web-video',
                'web_video_file',
                'public'
            );
        }

        if ($mobileVideoFile) {
            $this->fileUploadService->uploadFile(
                $setting,
                $mobileVideoFile,
                'website-home-page-setting/mobile-video',
                'mobile_video_file',
                'public'
            );
        }

        if ($videoProfileFile) {
            $this->fileUploadService->uploadFile(
                $setting,
                $videoProfileFile,
                'website-home-page-setting/video-profile',
                'video_profile_file',
                'public'
            );
        }

        return $setting->fresh();
    }

    public function updateWebsiteHomePageSetting(
        UuidInterface $id,
        array $data,
        ?UploadedFile $webVideoFile = null,
        ?UploadedFile $mobileVideoFile = null,
        ?UploadedFile $videoProfileFile = null
    ): WebsiteHomePageSetting {
        $setting = $this->findOneOrFail($id);

        $setting->update($data);

        if ($webVideoFile) {
            $this->fileUploadService->uploadFile(
                $setting,
                $webVideoFile,
                'website-home-page-setting/web-video',
                'web_video_file',
                'public'
            );
        }

        if ($mobileVideoFile) {
            $this->fileUploadService->uploadFile(
                $setting,
                $mobileVideoFile,
                'website-home-page-setting/mobile-video',
                'mobile_video_file',
                'public'
            );
        }

        if ($videoProfileFile) {
            $this->fileUploadService->uploadFile(
                $setting,
                $videoProfileFile,
                'website-home-page-setting/video-profile',
                'video_profile_file',
                'public'
            );
        }

        return $setting->fresh();
    }

    public function deleteWebsiteHomePageSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

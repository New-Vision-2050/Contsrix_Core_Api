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
use Modules\WebsiteCMS\WebsiteIcon\Enums\WebsiteIconCategoryType;
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

        return $websiteIcon->fresh();
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

        return $websiteIcon;
    }

    public function deleteWebsiteIcon(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getIconsByCategory(WebsiteIconCategoryType $categoryType, ?int $limit = null): Collection
    {
        $query = $this->model->where('website_icon_category_type', $categoryType->value)
            ->where('company_id', tenant('id'))
            ->where('status', 1);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getCompanyIcons(?int $limit = null): Collection
    {
        return $this->getIconsByCategory(WebsiteIconCategoryType::COMPANIES, $limit);
    }

    public function getApprovalIcons(?int $limit = null): Collection
    {
        return $this->getIconsByCategory(WebsiteIconCategoryType::APPROVALS, $limit);
    }

    public function getCertificatesIcons(?int $limit = null): Collection
    {
        return $this->getIconsByCategory(WebsiteIconCategoryType::CERTIFICATES, $limit);
    }

    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ): array
    {
        $query = $this->model
            ->newQuery()
            ->leftJoin('translations as t', function ($join) {
                $join->on('website_icons.id', '=', 't.translatable_id')
                    ->where('t.locale', app()->getLocale())
                    ->where('t.field', 'name')->where('t.translatable_type', WebsiteIcon::class);
            });

        if ($orderBy === 'name') {
            $query->orderBy('t.content', $sortBy);
        } else {
            $query->orderBy("website_icons.$orderBy", $sortBy);
        }
        $query->filter(request()->all());

        $count = (clone $query)->distinct('website_icons.id')->count('website_icons.id');

        $paginatedData = $query
            ->select('website_icons.*')
            ->forPage($page, $perPage)
            ->get();

        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSetting;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSettingDepartment;
use App\Traits\HasExport;

/**
 * @property WebsiteThemeSetting $model
 * @method WebsiteThemeSetting findOneOrFail($id)
 * @method WebsiteThemeSetting findOneByOrFail(array $data)
 */
class WebsiteThemeSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        WebsiteThemeSetting $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }


    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ) {

        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->where($conditions);
        } else {
            $query = $this->model->where($conditions);
        }
        $query->with(['departments', 'media']);

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->orderBy($orderBy, $sortBy)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function getWebsiteThemeSettingList(?int $page, ?int $perPage = 10)
    {
        return $this->model
            ->with(['departments', 'media'])
            ->paginate($perPage, ['*'], 'page', $page)
            ->getCollection();
    }

    public function getWebsiteThemeSetting(UuidInterface $id): WebsiteThemeSetting
    {
        return $this->model
            ->with(['departments', 'media'])
            ->findOrFail($id->toString());
    }

    public function createWebsiteThemeSetting(array $data, array $departments = [], ?UploadedFile $mainImage = null): WebsiteThemeSetting
    {
        return DB::transaction(function () use ($data, $departments, $mainImage) {
            // Create the theme setting
            $themeSetting = $this->create($data);

            // Create departments
            if (!empty($departments)) {
                foreach ($departments as $departmentData) {
                    WebsiteThemeSettingDepartment::create([
                        'website_theme_setting_id' => $themeSetting->id,
                        'name' => [
                            'ar' => $departmentData['name_ar'],
                            'en' => $departmentData['name_en'],
                        ],
                    ]);
                }
            }

            // Upload main image
            if ($mainImage) {
                $this->fileUploadService->uploadFile(
                    $themeSetting,
                    $mainImage,
                    'website-theme-setting/main-image',
                    'main_image',
                    'public'
                );
            }

            return $themeSetting->fresh(['departments', 'media']);
        });
    }

    public function updateWebsiteThemeSetting(
        UuidInterface $id,
        array $data,
        ?array $departments = null,
        ?UploadedFile $mainImage = null
    ): WebsiteThemeSetting {
        return DB::transaction(function () use ($id, $data, $departments, $mainImage) {
            $themeSetting = $this->findOneOrFail($id);

            // Update theme setting data
            if (!empty($data)) {
                $themeSetting->update($data);
            }

            // Update departments if provided
            if ($departments !== null) {
                // Delete old departments
                $themeSetting->departments()->delete();

                // Create new departments
                foreach ($departments as $departmentData) {
                    WebsiteThemeSettingDepartment::create([
                        'website_theme_setting_id' => $themeSetting->id,
                        'name' => [
                            'ar' => $departmentData['name_ar'],
                            'en' => $departmentData['name_en'],
                        ],
                    ]);
                }
            }

            // Upload new main image
            if ($mainImage) {
                $themeSetting->clearMediaCollection('main_image');
                $this->fileUploadService->uploadFile(
                    $themeSetting,
                    $mainImage,
                    'website-theme-setting/main-image',
                    'main_image',
                    'public'
                );
            }

            return $themeSetting->fresh(['departments', 'media']);
        });
    }

    public function deleteWebsiteThemeSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Assign theme setting to a company
     */
    public function assignThemeToCompany(UuidInterface $companyId, UuidInterface $themeSettingId): void
    {
        DB::transaction(function () use ($companyId, $themeSettingId) {
            // Remove any existing theme assignment for this company in pivot table
            DB::table('company_website_theme_settings')
                ->where('company_id', $companyId->toString())
                ->delete();

            // Assign new theme in pivot table
            DB::table('company_website_theme_settings')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'company_id' => $companyId->toString(),
                'website_theme_setting_id' => $themeSettingId->toString(),
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Get theme setting assigned to a company
     */
    public function getCompanyThemeSetting(UuidInterface $companyId): ?WebsiteThemeSetting
    {
        return $this->model
            ->with(['departments', 'media'])
            ->whereHas('companies', function ($query) use ($companyId) {
                $query->where('company_id', $companyId->toString());
            })
            ->first();
    }

    /**
     * Get default theme setting
     */
    public function getDefaultThemeSetting(): ?WebsiteThemeSetting
    {
        return $this->model
            ->with(['departments', 'media'])
            ->where('is_default', true)
            ->first();
    }
}

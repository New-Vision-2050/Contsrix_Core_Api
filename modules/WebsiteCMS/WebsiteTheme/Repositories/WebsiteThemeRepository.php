<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteColorPalette;
use App\Traits\HasExport;

/**
 * @property WebsiteTheme $model
 * @method WebsiteTheme findOneOrFail($id)
 * @method WebsiteTheme findOneByOrFail(array $data)
 */
class WebsiteThemeRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        WebsiteTheme $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getWebsiteThemeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteTheme(UuidInterface $id): WebsiteTheme
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteTheme(array $data): WebsiteTheme
    {
        return $this->create($data);
    }

    public function updateWebsiteTheme(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteTheme(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get the website theme for the current company
     */
    public function getCurrentCompanyTheme(): ?WebsiteTheme
    {
        return $this->model
            ->where('company_id', tenant('id'))
            ->with('colorPalettes')
            ->first();
    }

    /**
     * Update the website theme for the current company
     */
    public function updateCurrentCompanyTheme(array $data, ?array $colorPalettes = null, $icon = null): WebsiteTheme
    {
        return DB::transaction(function () use ($data, $colorPalettes, $icon) {
            $companyId = tenant('id');

            // Find or create theme for current company
            $theme = $this->model->firstOrCreate(
                ['company_id' => $companyId],
                array_merge($data, ['company_id' => $companyId])
            );

            // Update theme data if it exists
            if (!$theme->wasRecentlyCreated && !empty($data)) {
                $theme->update($data);
            }

            // Handle color palettes update by slug
            if ($colorPalettes !== null) {
                foreach ($colorPalettes as $paletteData) {
                    if (isset($paletteData['slug'])) {
                        // Update or create palette by slug
                        $palette = WebsiteColorPalette::where('slug', $paletteData['slug'])
                            ->where('website_theme_id', $theme->id)
                            ->first();

                        if ($palette) {
                            // Update existing palette
                            $palette->update([
                                'name' => $paletteData['name'] ?? $palette->name,
                                'primary' => $paletteData['primary'] ?? $palette->primary,
                                'light' => $paletteData['light'] ?? $palette->light,
                                'dark' => $paletteData['dark'] ?? $palette->dark,
                                'contrast' => $paletteData['contrast'] ?? $palette->contrast,
                            ]);
                        } else {
                            // Create new palette with slug
                            WebsiteColorPalette::create([
                                'website_theme_id' => $theme->id,
                                'slug' => $paletteData['slug'],
                                'name' => $paletteData['name'] ?? null,
                                'primary' => $paletteData['primary'] ?? null,
                                'light' => $paletteData['light'] ?? null,
                                'dark' => $paletteData['dark'] ?? null,
                                'contrast' => $paletteData['contrast'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // Handle icon upload
            if ($icon !== null) {
                $this->fileUploadService->uploadFile(
                    $theme,
                    $icon,
                    'website-theme/icon',
                    'icon',
                    'public'
                );
            }

            return $theme->fresh(['colorPalettes']);
        });
    }
}

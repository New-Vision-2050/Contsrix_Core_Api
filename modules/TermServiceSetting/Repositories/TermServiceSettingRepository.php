<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\TermServiceSetting\Models\TermServiceSetting;
use App\Traits\HasExport;

/**
 * @property TermServiceSetting $model
 * @method TermServiceSetting findOneOrFail($id)
 * @method TermServiceSetting findOneByOrFail(array $data)
 */
class TermServiceSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(TermServiceSetting $model)
    {
        parent::__construct($model);
    }

    public function getTermServiceSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->model
            ->with(['termSettings' => function ($query) {
                $query->with('parent');
            }])
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getAllTermServiceSettings(): Collection
    {
        return $this->model
            ->with(['termSettings' => function ($query) {
                $query->with(['parent.parent.parent.parent', 'children.children.children.children']);
            }])
            ->get();
    }

    public function getTermServiceSetting(int $id): TermServiceSetting
    {
        return $this->model
            ->with(['termSettings' => function ($query) {
                $query->with(['parent.parent.parent.parent', 'children.children.children.children']);
            }])
            ->findOrFail($id);
    }

    public function createTermServiceSetting(array $data, array $termSettingIds = []): TermServiceSetting
    {
        return DB::transaction(function () use ($data, $termSettingIds) {
            $data['company_id'] = tenant('company_id');

            $termServiceSetting = $this->create($data);

            if (!empty($termSettingIds)) {
                $termServiceSetting->termSettings()->sync($termSettingIds);
            }

            return $termServiceSetting->load(['termSettings' => function ($query) {
                $query->with(['parent.parent.parent.parent', 'children.children.children.children']);
            }]);
        });
    }

    public function updateTermServiceSetting(int $id, array $data, array $termSettingIds = []): TermServiceSetting
    {
        return DB::transaction(function () use ($id, $data, $termSettingIds) {
            $termServiceSetting = $this->findOneOrFail($id);
            $termServiceSetting->update($data);

            if (!empty($termSettingIds)) {
                $termServiceSetting->termSettings()->sync($termSettingIds);
            }

            return $termServiceSetting->load(['termSettings' => function ($query) {
                $query->with(['parent.parent.parent.parent', 'children.children.children.children']);
            }]);
        });
    }

    public function deleteTermServiceSetting(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $termServiceSetting = $this->findOneOrFail($id);

            // Check if there are any ClientRequests using this TermServiceSetting
            $hasClientRequests = \Modules\ClientRequest\Models\ClientRequestServiceTerm::where('term_service_setting_id', $id)->exists();

            if ($hasClientRequests) {
                throw new \Exception('Cannot delete TermServiceSetting: It is being used by one or more Client Requests');
            }

            // Delete from pivot table (term_service_setting_term_setting)
            $termServiceSetting->termSettings()->detach();

            // Delete the TermServiceSetting itself
            return $termServiceSetting->delete();
        });
    }
}

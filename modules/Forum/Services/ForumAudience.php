<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Modules\Forum\Enums\ForumVisibility;
use Modules\Forum\Models\Forum;
use Modules\User\Models\User;

class ForumAudience
{
    /**
     * Users who can see the forum, ignoring draft/archived status.
     *
     * @return array<int, string>
     */
    public function ids(Forum $forum, ?string $exceptUserId = null): array
    {
        $query = User::query()->where('company_id', $forum->company_id);

        if ($forum->visibility === ForumVisibility::SCOPED) {
            $branchIds = array_values(array_filter($forum->branch_ids ?? []));
            $projectIds = array_values(array_filter($forum->project_ids ?? []));
            $explicit = $forum->members()->pluck('user_id')
                ->merge($forum->admins()->pluck('user_id'))
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            $query->where(function ($query) use ($branchIds, $projectIds, $explicit) {
                $started = false;
                if ($branchIds !== []) {
                    $query->where(function ($query) use ($branchIds) {
                        $query->whereIn('management_hierarchy_id', $branchIds)
                            ->orWhereHas('professionalData', function ($professional) use ($branchIds) {
                                $professional->whereIn('branch_id', $branchIds)
                                    ->orWhereIn('management_id', $branchIds)
                                    ->orWhereIn('department_id', $branchIds);
                            });
                    });
                    $started = true;
                }
                if ($projectIds !== []) {
                    $method = $started ? 'orWhereHas' : 'whereHas';
                    $query->{$method}('projects', fn ($projects) => $projects->whereIn('projects.id', $projectIds));
                    $started = true;
                }
                if ($explicit !== []) {
                    $method = $started ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('id', $explicit);
                }
                if (! $started) {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        if ($exceptUserId !== null && $exceptUserId !== '') {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->pluck('id')->map(fn ($id) => (string) $id)->all();
    }
}

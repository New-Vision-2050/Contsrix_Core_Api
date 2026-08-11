<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

final class AttachmentRequestVisibilityService
{
    public function applyVisibleToCompany(Builder $query, string $companyId): Builder
    {
        return $query->where(function (Builder $query) use ($companyId): void {
            $query->where('attachment_requests.sender_company_id', $companyId)
                ->orWhereExists(static function ($query) use ($companyId): void {
                    $query->selectRaw('1')
                        ->from('projects')
                        ->whereColumn('projects.id', 'attachment_requests.project_id')
                        ->where('projects.company_id', $companyId);
                })
                ->orWhere(function (Builder $query) use ($companyId): void {
                    $this->applyReceiverCompanyVisibility($query, $companyId);
                });
        });
    }

    public function applyReceiverCompanyVisibility(Builder $query, string $companyId): Builder
    {
        return $query
            ->whereExists(static function ($query) use ($companyId): void {
                $query->selectRaw('1')
                    ->from('resource_shares')
                    ->join('projects', 'projects.id', '=', 'resource_shares.shareable_id')
                    ->where('resource_shares.shareable_type', ProjectManagement::class)
                    ->whereColumn('resource_shares.shareable_id', 'attachment_requests.project_id')
                    ->whereColumn('resource_shares.owner_company_id', 'projects.company_id')
                    ->where('resource_shares.shared_with_company_id', $companyId)
                    ->where('resource_shares.status', 'accepted');
            })
            ->whereExists(static function ($query) use ($companyId): void {
                $query->selectRaw('1')
                    ->from('project_procedure_settings')
                    ->whereColumn('project_procedure_settings.project_id', 'attachment_requests.project_id')
                    ->whereColumn('project_procedure_settings.procedure_setting_id', 'attachment_requests.procedure_setting_id')
                    ->whereExists(static function ($query): void {
                        $query->selectRaw('1')
                            ->from('projects')
                            ->whereColumn('projects.id', 'attachment_requests.project_id')
                            ->whereColumn('projects.company_id', 'project_procedure_settings.company_id');
                    })
                    ->where(static function ($query) use ($companyId): void {
                        $query->whereNotExists(static function ($query): void {
                            $query->selectRaw('1')
                                ->from('project_procedure_setting_receiver_companies')
                                ->whereColumn(
                                    'project_procedure_setting_receiver_companies.project_procedure_setting_id',
                                    'project_procedure_settings.id'
                                );
                        })
                            ->orWhereExists(static function ($query) use ($companyId): void {
                                $query->selectRaw('1')
                                    ->from('project_procedure_setting_receiver_companies')
                                    ->whereColumn(
                                        'project_procedure_setting_receiver_companies.project_procedure_setting_id',
                                        'project_procedure_settings.id'
                                    )
                                    ->where('project_procedure_setting_receiver_companies.company_id', $companyId);
                            });
                    });
            });
    }

    public function canCompanyView(string $requestId, string $companyId): bool
    {
        $query = AttachmentRequest::query()
            ->withoutGlobalScopes()
            ->whereKey($requestId);

        $this->applyVisibleToCompany($query, $companyId);

        return $query->exists();
    }

    public function assertCompanyCanView(AttachmentRequest $request, string $companyId): void
    {
        if (! $this->canCompanyView((string) $request->id, $companyId)) {
            abort(403, 'Unauthorized access to this attachment request.');
        }
    }
}

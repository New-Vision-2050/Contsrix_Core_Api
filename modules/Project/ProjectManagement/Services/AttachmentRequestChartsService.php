<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Process\Enums\ProcessStatus;
use Modules\Project\ProjectManagement\DTO\FilterAttachmentRequestChartsDTO;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Modules\User\Models\User;

class AttachmentRequestChartsService
{
    private const REQUEST_STATUSES = [
        AttachmentRequest::STATUS_PENDING,
        AttachmentRequest::STATUS_SEMI_APPROVED,
        AttachmentRequest::STATUS_APPROVED,
        AttachmentRequest::STATUS_DECLINED,
    ];

    private const ITEM_STATUSES = [
        'pending',
        'approved',
        'declined',
        'update_requested',
    ];

    public function __construct(
        private readonly AttachmentRequestVisibilityService $visibilityService,
    ) {}

    public function getChartsData(FilterAttachmentRequestChartsDTO $dto): array
    {
        return [
            'attachment_requests' => [
                'summary'         => $this->getAttachmentRequestSummary($dto),
                'status'          => $this->getAttachmentRequestStatusChart($dto),
                'direction'       => $this->getAttachmentRequestDirectionChart($dto),
                'procedure'       => $this->getAttachmentRequestProcedureChart($dto),
                'attachment_type' => $this->getAttachmentRequestAttachmentTypeChart($dto),
                'item_status'     => $this->getAttachmentRequestItemStatusChart($dto),
                'file_type'       => $this->getAttachmentRequestFileTypeChart($dto),
                'project'         => $this->getAttachmentRequestProjectChart($dto),
                'trend'           => $this->getAttachmentRequestTrendChart($dto),
            ],
            'requirement_submissions' => [
                'summary'     => $this->getRequirementSubmissionSummary($dto),
                'status'      => $this->getRequirementSubmissionStatusChart($dto),
                'direction'   => $this->getRequirementSubmissionDirectionChart($dto),
                'procedure'   => $this->getRequirementSubmissionProcedureChart($dto),
                'requirement' => $this->getRequirementSubmissionRequirementChart($dto),
                'file_type'   => $this->getRequirementSubmissionFileTypeChart($dto),
                'project'     => $this->getRequirementSubmissionProjectChart($dto),
                'trend'       => $this->getRequirementSubmissionTrendChart($dto),
            ],
        ];
    }

    private function baseAttachmentRequestQuery(
        FilterAttachmentRequestChartsDTO $dto,
        ?string $excludeDimension = null,
    ): Builder {
        $query = AttachmentRequest::query()->withoutGlobalScopes();
        $companyId = (string) tenant('id');

        $this->applyAttachmentDirectionScope(
            $query,
            $companyId,
            $excludeDimension === 'direction' ? null : $dto->direction,
        );

        if ($dto->projectId !== null && $excludeDimension !== 'project_id') {
            $query->where('attachment_requests.project_id', $dto->projectId);
        }

        if ($dto->contractualEngagementKey !== null) {
            $query->whereHas('project.contractualEngagement', function (Builder $query) use ($dto): void {
                $query->where('code', $dto->contractualEngagementKey);
            });
        }

        if ($dto->statusValues() !== [] && $excludeDimension !== 'status') {
            $query->whereIn('attachment_requests.status', $dto->statusValues());
        }

        if ($dto->procedureSettingId !== null && $excludeDimension !== 'procedure_setting_id') {
            $query->where('attachment_requests.procedure_setting_id', $dto->procedureSettingId);
        }

        if ($dto->attachmentTypeId !== null && $excludeDimension !== 'attachment_type_id') {
            $query->whereHas('projectProcedureSetting', function (Builder $query) use ($dto): void {
                $query->where('attachment_type_id', $dto->attachmentTypeId);
            });
        }

        if ($dto->itemStatusValues() !== [] && $excludeDimension !== 'item_status') {
            $query->whereHas('items', function (Builder $query) use ($dto): void {
                $query->whereIn('status', $dto->itemStatusValues());
            });
        }

        if ($dto->fileType !== null && $excludeDimension !== 'file_type') {
            $query->whereHas('items', function (Builder $query) use ($dto): void {
                $query->where('file_type', $dto->fileType);
            });
        }

        if ($dto->dateFrom !== null) {
            $query->whereDate('attachment_requests.date', '>=', $dto->dateFrom);
        }

        if ($dto->dateTo !== null) {
            $query->whereDate('attachment_requests.date', '<=', $dto->dateTo);
        }

        if ($dto->name !== null) {
            $query->where('attachment_requests.serial_number', 'like', '%' . $dto->name . '%');
        }

        return $query;
    }

    private function baseRequirementSubmissionQuery(
        FilterAttachmentRequestChartsDTO $dto,
        ?string $excludeDimension = null,
    ): Builder {
        $query = ProjectRequirementSubmission::query()->withoutGlobalScopes();
        $companyId = (string) tenant('id');

        if ($dto->name !== null) {
            return $query->whereRaw('1 = 0');
        }

        $this->applySubmissionDirectionScope(
            $query,
            $companyId,
            $excludeDimension === 'direction' ? null : $dto->direction,
        );

        if ($dto->projectId !== null && $excludeDimension !== 'project_id') {
            $query->where('project_requirement_submissions.project_id', $dto->projectId);
        }

        if ($dto->contractualEngagementKey !== null) {
            $query->whereHas('project.contractualEngagement', function (Builder $query) use ($dto): void {
                $query->where('code', $dto->contractualEngagementKey);
            });
        }

        if ($dto->statusValues() !== [] && $excludeDimension !== 'status') {
            $this->applySubmissionStatusScope($query, $dto->statusValues());
        }

        if ($dto->procedureSettingId !== null && $excludeDimension !== 'procedure_setting_id') {
            $query->whereHas('requirement', function (Builder $query) use ($dto): void {
                $query->where('procedure_setting_id', $dto->procedureSettingId);
            });
        }

        if ($dto->projectRequirementId !== null && $excludeDimension !== 'project_requirement_id') {
            $query->where('project_requirement_submissions.project_requirement_id', $dto->projectRequirementId);
        }

        if ($dto->fileType !== null && $excludeDimension !== 'file_type') {
            $query->whereHas('media', function (Builder $query) use ($dto): void {
                $query->where('collection_name', 'files')
                    ->where('mime_type', $dto->fileType);
            });
        }

        if ($dto->dateFrom !== null) {
            $query->whereDate('project_requirement_submissions.created_at', '>=', $dto->dateFrom);
        }

        if ($dto->dateTo !== null) {
            $query->whereDate('project_requirement_submissions.created_at', '<=', $dto->dateTo);
        }

        return $query;
    }

    private function getAttachmentRequestSummary(FilterAttachmentRequestChartsDTO $dto): array
    {
        $base = $this->baseAttachmentRequestQuery($dto);
        $companyId = (string) tenant('id');

        return [
            'total_requests'         => (clone $base)->count(),
            'total_items'            => $this->attachmentItemsQuery($dto)->count(),
            'pending_requests'       => (clone $base)->where('attachment_requests.status', AttachmentRequest::STATUS_PENDING)->count(),
            'semi_approved_requests' => (clone $base)->where('attachment_requests.status', AttachmentRequest::STATUS_SEMI_APPROVED)->count(),
            'approved_requests'      => (clone $base)->where('attachment_requests.status', AttachmentRequest::STATUS_APPROVED)->count(),
            'declined_requests'      => (clone $base)->where('attachment_requests.status', AttachmentRequest::STATUS_DECLINED)->count(),
            'outgoing_requests'      => (clone $base)->where('attachment_requests.sender_company_id', $companyId)->count(),
            'incoming_requests'      => (clone $base)->where('attachment_requests.sender_company_id', '!=', $companyId)->count(),
        ];
    }

    private function getAttachmentRequestStatusChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseAttachmentRequestQuery($dto, 'status')
            ->whereIn('attachment_requests.status', self::REQUEST_STATUSES)
            ->select('attachment_requests.status as code', DB::raw('count(*) as count'))
            ->groupBy('attachment_requests.status')
            ->get();

        return $this->distribution($rows->map(fn ($row): array => [
            'code'  => (string) $row->code,
            'label' => $this->requestStatusLabel((string) $row->code),
            'count' => (int) $row->count,
        ])->all());
    }

    private function getAttachmentRequestDirectionChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $base = $this->baseAttachmentRequestQuery($dto, 'direction');
        $companyId = (string) tenant('id');

        return $this->distribution([
            [
                'code'  => 'outgoing',
                'label' => $this->directionLabel('outgoing'),
                'count' => (clone $base)->where('attachment_requests.sender_company_id', $companyId)->count(),
            ],
            [
                'code'  => 'incoming',
                'label' => $this->directionLabel('incoming'),
                'count' => (clone $base)->where('attachment_requests.sender_company_id', '!=', $companyId)->count(),
            ],
        ]);
    }

    private function getAttachmentRequestProcedureChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseAttachmentRequestQuery($dto, 'procedure_setting_id')
            ->leftJoin('procedure_settings', 'procedure_settings.id', '=', 'attachment_requests.procedure_setting_id')
            ->whereNotNull('attachment_requests.procedure_setting_id')
            ->select(
                'procedure_settings.id as code',
                'procedure_settings.name as label',
                DB::raw('count(distinct attachment_requests.id) as count'),
            )
            ->groupBy('procedure_settings.id', 'procedure_settings.name')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getAttachmentRequestAttachmentTypeChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseAttachmentRequestQuery($dto, 'attachment_type_id')
            ->leftJoin('project_procedure_settings as pps', function ($join): void {
                $join->on('pps.procedure_setting_id', '=', 'attachment_requests.procedure_setting_id')
                    ->on('pps.project_id', '=', 'attachment_requests.project_id');
            })
            ->leftJoin('folders as attachment_types', 'attachment_types.id', '=', 'pps.attachment_type_id')
            ->whereNotNull('pps.attachment_type_id')
            ->select(
                'pps.attachment_type_id as code',
                'attachment_types.name as label',
                DB::raw('count(distinct attachment_requests.id) as count'),
            )
            ->groupBy('pps.attachment_type_id', 'attachment_types.name')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getAttachmentRequestItemStatusChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->attachmentItemsQuery($dto, 'item_status')
            ->whereIn('attachment_request_items.status', self::ITEM_STATUSES)
            ->select('attachment_request_items.status as code', DB::raw('count(*) as count'))
            ->groupBy('attachment_request_items.status')
            ->orderByDesc('count')
            ->get();

        return $this->distribution($rows->map(fn ($row): array => [
            'code'  => (string) $row->code,
            'label' => $this->itemStatusLabel((string) $row->code),
            'count' => (int) $row->count,
        ])->all());
    }

    private function getAttachmentRequestFileTypeChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->attachmentItemsQuery($dto, 'file_type')
            ->whereNotNull('attachment_request_items.file_type')
            ->select('attachment_request_items.file_type as code', DB::raw('count(*) as count'))
            ->groupBy('attachment_request_items.file_type')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getAttachmentRequestProjectChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseAttachmentRequestQuery($dto, 'project_id')
            ->leftJoin('projects', 'projects.id', '=', 'attachment_requests.project_id')
            ->select(
                'projects.id as code',
                'projects.name as label',
                DB::raw('count(distinct attachment_requests.id) as count'),
            )
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getAttachmentRequestTrendChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseAttachmentRequestQuery($dto)
            ->select(
                DB::raw("DATE_FORMAT(attachment_requests.date, '%Y-%m') as month"),
                DB::raw('count(*) as count'),
            )
            ->whereNotNull('attachment_requests.date')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'total' => (int) $rows->sum('count'),
            'data'  => $rows->map(fn ($row): array => [
                'month' => $row->month,
                'count' => (int) $row->count,
            ])->values()->all(),
        ];
    }

    private function getRequirementSubmissionSummary(FilterAttachmentRequestChartsDTO $dto): array
    {
        $base = $this->baseRequirementSubmissionQuery($dto);
        $companyId = (string) tenant('id');

        return [
            'total_submissions'    => (clone $base)->count(),
            'total_files'          => $this->submissionFilesQuery($dto)->count(),
            'pending_submissions'  => $this->countSubmissionsByStatus(clone $base, 'pending'),
            'approved_submissions' => $this->countSubmissionsByStatus(clone $base, 'approved'),
            'declined_submissions' => $this->countSubmissionsByStatus(clone $base, 'declined'),
            'outgoing_submissions' => $this->applyUploaderScope(clone $base, $companyId)->count(),
            'incoming_submissions' => $this->applyNotUploaderScope(clone $base, $companyId)->count(),
        ];
    }

    private function getRequirementSubmissionStatusChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $base = $this->baseRequirementSubmissionQuery($dto, 'status');

        return $this->distribution([
            [
                'code'  => 'pending',
                'label' => $this->requestStatusLabel('pending'),
                'count' => $this->countSubmissionsByStatus(clone $base, 'pending'),
            ],
            [
                'code'  => 'approved',
                'label' => $this->requestStatusLabel('approved'),
                'count' => $this->countSubmissionsByStatus(clone $base, 'approved'),
            ],
            [
                'code'  => 'declined',
                'label' => $this->requestStatusLabel('declined'),
                'count' => $this->countSubmissionsByStatus(clone $base, 'declined'),
            ],
        ]);
    }

    private function getRequirementSubmissionDirectionChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $base = $this->baseRequirementSubmissionQuery($dto, 'direction');
        $companyId = (string) tenant('id');

        return $this->distribution([
            [
                'code'  => 'outgoing',
                'label' => $this->directionLabel('outgoing'),
                'count' => $this->applyUploaderScope(clone $base, $companyId)->count(),
            ],
            [
                'code'  => 'incoming',
                'label' => $this->directionLabel('incoming'),
                'count' => $this->applyNotUploaderScope(clone $base, $companyId)->count(),
            ],
        ]);
    }

    private function getRequirementSubmissionProcedureChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseRequirementSubmissionQuery($dto, 'procedure_setting_id')
            ->leftJoin('project_requirements', 'project_requirements.id', '=', 'project_requirement_submissions.project_requirement_id')
            ->leftJoin('procedure_settings', 'procedure_settings.id', '=', 'project_requirements.procedure_setting_id')
            ->whereNotNull('project_requirements.procedure_setting_id')
            ->select(
                'procedure_settings.id as code',
                'procedure_settings.name as label',
                DB::raw('count(distinct project_requirement_submissions.id) as count'),
            )
            ->groupBy('procedure_settings.id', 'procedure_settings.name')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getRequirementSubmissionRequirementChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseRequirementSubmissionQuery($dto, 'project_requirement_id')
            ->leftJoin('project_requirements', 'project_requirements.id', '=', 'project_requirement_submissions.project_requirement_id')
            ->select(
                'project_requirements.id as code',
                'project_requirements.required_document_name as label',
                DB::raw('count(distinct project_requirement_submissions.id) as count'),
            )
            ->groupBy('project_requirements.id', 'project_requirements.required_document_name')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getRequirementSubmissionFileTypeChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->submissionFilesQuery($dto, 'file_type')
            ->whereNotNull('media.mime_type')
            ->select('media.mime_type as code', DB::raw('count(*) as count'))
            ->groupBy('media.mime_type')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getRequirementSubmissionProjectChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseRequirementSubmissionQuery($dto, 'project_id')
            ->leftJoin('projects', 'projects.id', '=', 'project_requirement_submissions.project_id')
            ->select(
                'projects.id as code',
                'projects.name as label',
                DB::raw('count(distinct project_requirement_submissions.id) as count'),
            )
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('count')
            ->get();

        return $this->rowsDistribution($rows);
    }

    private function getRequirementSubmissionTrendChart(FilterAttachmentRequestChartsDTO $dto): array
    {
        $rows = $this->baseRequirementSubmissionQuery($dto)
            ->select(
                DB::raw("DATE_FORMAT(project_requirement_submissions.created_at, '%Y-%m') as month"),
                DB::raw('count(*) as count'),
            )
            ->whereNotNull('project_requirement_submissions.created_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'total' => (int) $rows->sum('count'),
            'data'  => $rows->map(fn ($row): array => [
                'month' => $row->month,
                'count' => (int) $row->count,
            ])->values()->all(),
        ];
    }

    private function attachmentItemsQuery(
        FilterAttachmentRequestChartsDTO $dto,
        ?string $excludeDimension = null,
    ): Builder {
        return AttachmentRequestItem::query()
            ->whereIn(
                'attachment_request_items.attachment_request_id',
                $this->baseAttachmentRequestQuery($dto, $excludeDimension)
                    ->select('attachment_requests.id'),
            );
    }

    private function submissionFilesQuery(
        FilterAttachmentRequestChartsDTO $dto,
        ?string $excludeDimension = null,
    ): \Illuminate\Database\Query\Builder {
        return DB::table('media')
            ->whereIn('media.model_type', [
                ProjectRequirementSubmission::class,
                ProjectRequirementSubmission::PROCESSABLE_TYPE,
            ])
            ->where('media.collection_name', 'files')
            ->whereIn(
                'media.model_id',
                $this->baseRequirementSubmissionQuery($dto, $excludeDimension)
                    ->select('project_requirement_submissions.id'),
            );
    }

    private function applyAttachmentDirectionScope(Builder $query, string $companyId, ?string $direction): void
    {
        if ($direction === 'outgoing') {
            $query->where('attachment_requests.sender_company_id', $companyId);

            return;
        }

        if ($direction === 'incoming') {
            $this->visibilityService->applyReceiverCompanyVisibility($query, $companyId);

            return;
        }

        $this->visibilityService->applyVisibleToCompany($query, $companyId);
    }

    private function applySubmissionDirectionScope(Builder $query, string $companyId, ?string $direction): void
    {
        if ($direction === 'outgoing') {
            $this->applyUploaderScope($query, $companyId);

            return;
        }

        if ($direction === 'incoming') {
            $this->applyActionTakerScope($query, $companyId);

            return;
        }

        $query->where(function (Builder $query) use ($companyId): void {
            $query->where(function (Builder $query) use ($companyId): void {
                $this->applyUploaderScope($query, $companyId);
            })->orWhere(function (Builder $query) use ($companyId): void {
                $this->applyActionTakerScope($query, $companyId);
            });
        });
    }

    private function applyActionTakerScope(Builder $query, string $companyId): Builder
    {
        $companyUserIds = $this->companyUserIds($companyId);

        if ($companyUserIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('processes', function (Builder $query) use ($companyUserIds): void {
            $query->whereHas('steps', function (Builder $query) use ($companyUserIds): void {
                $query->where(function (Builder $query) use ($companyUserIds): void {
                    $query->whereIn('assigned_user_id', $companyUserIds);

                    foreach ($companyUserIds as $userId) {
                        $query->orWhereJsonContains('authorized_user_ids', $userId);
                    }
                });
            });
        });
    }

    private function applyUploaderScope(Builder $query, string $companyId): Builder
    {
        return $query->whereHas('processes', function (Builder $query) use ($companyId): void {
            $query->where('metadata->uploader_company_id', $companyId);
        });
    }

    private function applyNotUploaderScope(Builder $query, string $companyId): Builder
    {
        return $query->whereDoesntHave('processes', function (Builder $query) use ($companyId): void {
            $query->where('metadata->uploader_company_id', $companyId);
        });
    }

    /**
     * @param  list<string>  $statuses
     */
    private function applySubmissionStatusScope(Builder $query, array $statuses): Builder
    {
        return $query->where(function (Builder $query) use ($statuses): void {
            foreach ($statuses as $status) {
                $query->orWhere(function (Builder $query) use ($status): void {
                    match ($status) {
                        'approved' => $query->where(function (Builder $query): void {
                            $query->whereDoesntHave('processes')
                                ->orWhereHas('processes', function (Builder $query): void {
                                    $query->where('status', ProcessStatus::Completed->value);
                                });
                        }),
                        'declined' => $query->whereHas('processes', function (Builder $query): void {
                            $query->where('status', ProcessStatus::Failed->value);
                        }),
                        'pending' => $query->whereHas('processes', function (Builder $query): void {
                            $query->whereIn('status', [
                                ProcessStatus::Pending->value,
                                ProcessStatus::InProgress->value,
                            ]);
                        }),
                        default => $query->whereRaw('1 = 0'),
                    };
                });
            }
        });
    }

    private function countSubmissionsByStatus(Builder $query, string $status): int
    {
        return $this->applySubmissionStatusScope($query, [$status])->count();
    }

    /**
     * @return list<string>
     */
    private function companyUserIds(string $companyId): array
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();
    }

    private function rowsDistribution(iterable $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'code'  => $row->code,
                'label' => $row->label ?? $row->code ?? __('غير محدد'),
                'count' => (int) $row->count,
            ];
        }

        return $this->distribution($items);
    }

    /**
     * @param  array<int, array{code: mixed, label: mixed, count: int}>  $items
     */
    private function distribution(array $items): array
    {
        $items = array_values(array_filter(
            $items,
            static fn (array $item): bool => (int) $item['count'] > 0,
        ));

        $total = array_sum(array_map(static fn (array $item): int => (int) $item['count'], $items));

        $data = array_map(static function (array $item) use ($total): array {
            $count = (int) $item['count'];

            return [
                'code'       => $item['code'],
                'label'      => $item['label'],
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            ];
        }, $items);

        return [
            'total' => (int) $total,
            'data'  => $data,
        ];
    }

    private function requestStatusLabel(string $status): string
    {
        $locale = app()->getLocale();

        $labels = [
            'pending'       => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            'semi-approved' => ['ar' => 'موافقة جزئية', 'en' => 'Semi Approved'],
            'approved'      => ['ar' => 'موافق عليه', 'en' => 'Approved'],
            'declined'      => ['ar' => 'مرفوض', 'en' => 'Declined'],
        ];

        return $labels[$status][$locale] ?? $labels[$status]['en'] ?? $status;
    }

    private function itemStatusLabel(string $status): string
    {
        $locale = app()->getLocale();

        $labels = [
            'pending'          => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            'approved'         => ['ar' => 'موافق عليه', 'en' => 'Approved'],
            'declined'         => ['ar' => 'مرفوض', 'en' => 'Declined'],
            'update_requested' => ['ar' => 'مطلوب تحديث', 'en' => 'Update Requested'],
        ];

        return $labels[$status][$locale] ?? $labels[$status]['en'] ?? $status;
    }

    private function directionLabel(string $direction): string
    {
        $locale = app()->getLocale();

        $labels = [
            'outgoing' => ['ar' => 'صادرة', 'en' => 'Outgoing'],
            'incoming' => ['ar' => 'واردة', 'en' => 'Incoming'],
        ];

        return $labels[$direction][$locale] ?? $labels[$direction]['en'] ?? $direction;
    }
}

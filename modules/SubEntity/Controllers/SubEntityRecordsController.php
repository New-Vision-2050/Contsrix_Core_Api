<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Presenters\CompanyUserClientPresenter;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\SubEntity\Exports\SubEntityRecordsExport;
use Modules\SubEntity\Requests\ExportSubEntityRecordsRequest;
use Modules\SubEntity\Requests\GetSubEntityRecordsRequest;
use Modules\SubEntity\Requests\UpdateSubEntityRecordAttendanceStatusRequest;
use Modules\SubEntity\Services\RegistrationFormCRUDService;
use Modules\SubEntity\Services\SubEntityRecordsService;

class SubEntityRecordsController extends Controller
{
    public function __construct(
        private SubEntityRecordsService $subEntityRecordsService,
        private RegistrationFormCRUDService $registrationFormCRUDService,
    ) {}

    public function index(GetSubEntityRecordsRequest $request)
    {
        $list = $this->subEntityRecordsService->getRecords(
            $request->get('sub_entity_id'),
            $request->get('registration_form_id'),
            $request->get('branch_id'),
            (int) $request->get('page', 1),
            $this->subEntityRecordsService->resolvePerPage($request->get('per_page'))
        );
        $registrationForm = $this->registrationFormCRUDService->getById($request->get('registration_form_id'));
        $role = $registrationForm->company_user_role_map;

        if ($role == CompanyUserRole::CLIENT->value) {
            return Json::items(CompanyUserClientPresenter::collection($list['data'] ?? [], $role), paginationSettings: $list['pagination'] ?? []);
        }

        $records = $list['data'] ?? [];
        $presented = CompanyUserPresenter::collection($records, $role);

        if ((int) $role === CompanyUserRole::EMPLOYEE->value) {
            $presented = $this->subEntityRecordsService->attachAttendanceToEmployeeRows(
                $records,
                $presented,
                $request->get('start_date') ?: now()->toDateString()
            );
        }

        return Json::items($presented, paginationSettings: $list['pagination'] ?? []);
    }

    public function updateAttendanceStatus(UpdateSubEntityRecordAttendanceStatusRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $payload = $this->subEntityRecordsService->setAttendanceStatusForCompanyUser(
            $validated['company_user_id'],
            $validated['status'],
            [
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ]
        );

        return Json::item($payload, message: 'Attendance status updated successfully');
    }

    public function widgets(GetSubEntityRecordsRequest $request): JsonResponse
    {
        $widgetsData = $this->subEntityRecordsService->getWidgetsData(
            $request->get('sub_entity_id'),
            $request->get('registration_form_id')
        );

        return Json::item($widgetsData, message: __('messages.sub_entity_records.widgets_retrieved'));
    }

    /**
     * Export sub entity records to a file
     */
    public function export(ExportSubEntityRecordsRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'sub_entity_records.'.$format;

        $filters = $request->getFilters();

        return Excel::download(new SubEntityRecordsExport($this->subEntityRecordsService, $filters), $fileName);
    }
}

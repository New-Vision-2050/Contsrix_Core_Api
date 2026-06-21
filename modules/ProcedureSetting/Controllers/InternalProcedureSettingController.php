<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\ProcedureSetting\Presenters\InternalProcedureSettingPresenter;
use Modules\ProcedureSetting\Requests\CreateInternalProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\GetInternalProcedureSettingListRequest;
use Modules\ProcedureSetting\Requests\SetStatusInternalProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\UpdateInternalProcedureSettingRequest;
use Modules\ProcedureSetting\Services\InternalProcedureSettingService;

class InternalProcedureSettingController extends Controller
{
    public function __construct(
        private readonly InternalProcedureSettingService $service,
    ) {}

    /**
     * GET /procedure-settings/internal-procedures
     * List all InternalProcedureSettings with optional type filter.
     */
    public function index(GetInternalProcedureSettingListRequest $request): JsonResponse
    {
        $items = $this->service->listAll($request->getType());

        return Json::items(InternalProcedureSettingPresenter::collection($items));
    }

    /**
     * GET /procedure-settings/{id}/internal-procedures/by-form/{formKey}
     * Get a single InternalProcedureSetting by its form key under a parent.
     */
    public function showByForm(string $id, string $formKey): JsonResponse
    {
        $setting = $this->service->resolveByForm($id, $formKey);

        if ($setting === null) {
            return Json::error(__('Internal procedure setting not found for form key: :form', ['form' => $formKey]), 404);
        }

        return Json::item(
            InternalProcedureSettingPresenter::single($setting),
            message: 'Internal procedure setting retrieved successfully',
        );
    }

    /**
     * GET /procedure-settings/{id}/available-forms
     * Returns forms applicable to this parent's type, for admin dropdowns.
     */
    public function availableForms(string $id): JsonResponse
    {
        $forms = $this->service->availableFormsForParent($id);

        return Json::items($forms, message: 'Available forms retrieved successfully');
    }

    /**
     * POST /procedure-settings/internal-procedures
     * Create internal procedure by type (finds parent automatically).
     */
    public function store(CreateInternalProcedureSettingRequest $request): JsonResponse
    {
        $data = $request->toData();
        $parent = $this->service->findParentByType(
            $data['type'] ?? '',
            tenant('id') ? (string) tenant('id') : null,
        );

        if ($parent === null) {
            return Json::error(__('Parent procedure setting not found for type: :type', ['type' => $data['type'] ?? '']), 404);
        }

        $setting = $this->service->create((string) $parent->id, $data);

        return Json::item(
            InternalProcedureSettingPresenter::single($setting),
            message: 'Internal procedure setting created successfully',
        );
    }

    /**
     * PUT /procedure-settings/{id}/internal-procedures/{internalProcedureId}
     */
    public function update(UpdateInternalProcedureSettingRequest $request, string $id, string $internalProcedureId): JsonResponse
    {
        $setting = $this->service->update($id, $internalProcedureId, $request->toData());

        return Json::item(
            InternalProcedureSettingPresenter::single($setting),
            message: 'Internal procedure setting updated successfully',
        );
    }

    /**
     * PUT /procedure-settings/{id}/internal-procedures/{internalProcedureId}/set-status
     */
    public function setStatus(SetStatusInternalProcedureSettingRequest $request, string $id, string $internalProcedureId): JsonResponse
    {
        $setting = $this->service->setStatus($id, $internalProcedureId, $request->isActive());

        return Json::item(
            InternalProcedureSettingPresenter::single($setting),
            message: 'Internal procedure setting status updated successfully',
        );
    }

    /**
     * DELETE /procedure-settings/{id}/internal-procedures/{internalProcedureId}
     */
    public function destroy(string $id, string $internalProcedureId): JsonResponse
    {
        $this->service->delete($id, $internalProcedureId);

        return Json::deleted('Internal procedure setting deleted successfully');
    }
}

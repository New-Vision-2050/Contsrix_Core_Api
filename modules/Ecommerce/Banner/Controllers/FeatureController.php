<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Services\FeatureCRUDService;
use Modules\Ecommerce\Banner\Requests\CreateFeatureRequest;
use Modules\Ecommerce\Banner\Requests\UpdateFeatureRequest;
use Modules\Ecommerce\Banner\Requests\GetFeatureRequest;
use Modules\Ecommerce\Banner\Presenters\FeaturePresenter;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function __construct(
        private FeatureCRUDService $featureService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $list = $this->featureService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FeaturePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFeatureRequest $request): JsonResponse
    {
        $item = $this->featureService->get(Uuid::fromString($request->route('id')));

        $presenter = new FeaturePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFeatureRequest $request): JsonResponse
    {
        $feature = $this->featureService->create($request->createCreateFeatureDTO());

        $presenter = new FeaturePresenter($feature);

        return Json::item($presenter->getData(), message: 'تم إنشاء الميزة بنجاح');
    }

    public function update(UpdateFeatureRequest $request): JsonResponse
    {
        $featureId = Uuid::fromString($request->route('id'));
        $updatedFeature = $this->featureService->update($featureId, $request->getUpdateData());

        $presenter = new FeaturePresenter($updatedFeature);

        return Json::item($presenter->getData(), message: 'تم تحديث الميزة بنجاح');
    }

    public function toggleStatus(GetFeatureRequest $request): JsonResponse
    {
        $featureId = Uuid::fromString($request->route('id'));
        $updatedFeature = $this->featureService->toggleStatus($featureId);
        
        $presenter = new FeaturePresenter($updatedFeature);
        
        $message = $updatedFeature->is_active ? 'تم تفعيل الميزة بنجاح' : 'تم إلغاء تفعيل الميزة بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }

    public function destroy(GetFeatureRequest $request): JsonResponse
    {
        $featureId = Uuid::fromString($request->route('id'));
        $this->featureService->delete($featureId);

        return Json::deleted();
    }

    public function getByCompany(Request $request): JsonResponse
    {
        $companyId = Uuid::fromString($request->get('company_id'));
        $features = $this->featureService->getByCompany($companyId);

        return Json::items($features, message: 'تم جلب مميزات الشركة بنجاح');
    }

    public function getActiveFeatures(): JsonResponse
    {
        $features = $this->featureService->getActiveFeatures();

        return Json::items($features, message: 'تم جلب المميزات النشطة بنجاح');
    }
}

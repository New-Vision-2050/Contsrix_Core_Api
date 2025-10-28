<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Services\StoreBranchCRUDService;
use Modules\Ecommerce\Banner\Requests\CreateStoreBranchRequest;
use Modules\Ecommerce\Banner\Requests\UpdateStoreBranchRequest;
use Modules\Ecommerce\Banner\Requests\GetStoreBranchRequest;
use Modules\Ecommerce\Banner\Presenters\StoreBranchPresenter;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;

class StoreBranchController extends Controller
{
    public function __construct(
        private StoreBranchCRUDService $storeBranchService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $list = $this->storeBranchService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(StoreBranchPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetStoreBranchRequest $request): JsonResponse
    {
        $item = $this->storeBranchService->get(Uuid::fromString($request->route('id')));

        $presenter = new StoreBranchPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateStoreBranchRequest $request): JsonResponse
    {
        $storeBranch = $this->storeBranchService->create($request->createCreateStoreBranchDTO());

        $presenter = new StoreBranchPresenter($storeBranch);

        return Json::item($presenter->getData(), message: 'تم إنشاء الفرع بنجاح');
    }

    public function update(UpdateStoreBranchRequest $request): JsonResponse
    {
        $storeBranchId = Uuid::fromString($request->route('id'));
        $updatedStoreBranch = $this->storeBranchService->update($storeBranchId, $request->getUpdateData());

        $presenter = new StoreBranchPresenter($updatedStoreBranch);

        return Json::item($presenter->getData(), message: 'تم تحديث الفرع بنجاح');
    }

    public function toggleStatus(GetStoreBranchRequest $request): JsonResponse
    {
        $storeBranchId = Uuid::fromString($request->route('id'));
        $updatedStoreBranch = $this->storeBranchService->toggleStatus($storeBranchId);
        
        $presenter = new StoreBranchPresenter($updatedStoreBranch);
        
        $message = $updatedStoreBranch->is_active ? 'تم تفعيل الفرع بنجاح' : 'تم إلغاء تفعيل الفرع بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }

    public function destroy(GetStoreBranchRequest $request): JsonResponse
    {
        $storeBranchId = Uuid::fromString($request->route('id'));
        $this->storeBranchService->delete($storeBranchId);

        return Json::deleted();
    }

    public function getByType(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $storeBranches = $this->storeBranchService->getByType($type);

        return Json::items($storeBranches, message: 'تم جلب الفروع حسب النوع بنجاح');
    }

    public function getByCountry(Request $request): JsonResponse
    {
        $countryId = $request->get('country_id');
        $storeBranches = $this->storeBranchService->getByCountry(Uuid::fromString($countryId));

        return Json::items($storeBranches, message: 'تم جلب الفروع حسب الدولة بنجاح');
    }

    public function getActiveStoreBranches(): JsonResponse
    {
        $storeBranches = $this->storeBranchService->getActiveStoreBranches();

        return Json::items($storeBranches, message: 'تم جلب الفروع النشطة بنجاح');
    }

    public function searchByName(Request $request): JsonResponse
    {
        $name = $request->get('name');
        $storeBranches = $this->storeBranchService->searchByName($name);

        return Json::items($storeBranches, message: 'تم البحث في الفروع بنجاح');
    }

    public function getTypes(): JsonResponse
    {
        $types = [
            'main' => 'الفرع الرئيسي',
            'branch' => 'فرع',
            'warehouse' => 'مستودع',
            'showroom' => 'معرض',
            'office' => 'مكتب'
        ];

        return Json::items($types, message: 'تم جلب أنواع الفروع بنجاح');
    }
}

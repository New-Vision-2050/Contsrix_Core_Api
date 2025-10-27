<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Services\SettingPageCRUDService;
use Modules\Ecommerce\Banner\Requests\UpsertSettingPageRequest;
use Modules\Ecommerce\Banner\Requests\GetSettingPageRequest;
use Modules\Ecommerce\Banner\Requests\GetSettingPageListRequest;
use Modules\Ecommerce\Banner\Requests\DeleteSettingPageRequest;
use Modules\Ecommerce\Banner\Presenters\SettingPagePresenter;
use Ramsey\Uuid\Uuid;

class SettingPageController extends Controller
{
    public function __construct(
        private SettingPageCRUDService $settingPageService,
    ) {
    }

    public function index(GetSettingPageListRequest $request): JsonResponse
    {
        $list = $this->settingPageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SettingPagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSettingPageRequest $request): JsonResponse
    {
        $item = $this->settingPageService->get(Uuid::fromString($request->route('id')));

        $presenter = new SettingPagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsert(UpsertSettingPageRequest $request): JsonResponse
    {
        $settingPage = $this->settingPageService->upsert($request->createUpsertSettingPageDTO());

        $presenter = new SettingPagePresenter($settingPage);

        $message = 'تم حفظ إعدادات الصفحة بنجاح';

        return Json::item($presenter->getData(), message: $message);
    }

    public function toggleStatus(GetSettingPageRequest $request): JsonResponse
    {
        $settingPageId = Uuid::fromString($request->route('id'));
        $updatedSettingPage = $this->settingPageService->toggleStatus($settingPageId);
        
        $presenter = new SettingPagePresenter($updatedSettingPage);
        
        $message = $updatedSettingPage->is_active ? 'تم تفعيل إعدادات الصفحة بنجاح' : 'تم إلغاء تفعيل إعدادات الصفحة بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }

    public function delete(DeleteSettingPageRequest $request): JsonResponse
    {
        $this->settingPageService->delete(Uuid::fromString($request->route('id')));

        return Json::deleted('تم حذف إعدادات الصفحة بنجاح');
    }

    public function getByType(GetSettingPageListRequest $request): JsonResponse
    {
        $type = $request->get('type');
        $companyId = Uuid::fromString($request->get('company_id'));
        
        $settingPage = $this->settingPageService->getByCompanyAndType($companyId, $type);
        
        if (!$settingPage) {
            return Json::item(null, message: 'لم يتم العثور على إعدادات الصفحة');
        }

        $presenter = new SettingPagePresenter($settingPage);

        return Json::item($presenter->getData());
    }
}

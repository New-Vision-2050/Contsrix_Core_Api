<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Controllers\Website;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Banner\Presenters\SettingPagePresenter;
use Modules\Ecommerce\Banner\Requests\Website\GetSettingPageByTypeWebsiteRequest;
use Modules\Ecommerce\Banner\Services\Website\SettingPageWebsiteService;

class SettingPageWebsiteController extends Controller
{
    public function __construct(
        private SettingPageWebsiteService $settingPageService,
    ) {
    }

    public function getByType(GetSettingPageByTypeWebsiteRequest $request): JsonResponse
    {
        $type = $request->get('type');
        
        $settingPage = $this->settingPageService->getByType($type);
        
        if (!$settingPage) {
            return response()->json([
                'code' => 'SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT',
                'message' => 'لم يتم العثور على إعدادات الصفحة',
                'payload' => null,
            ]);
        }

        $presenter = new SettingPagePresenter($settingPage);

        return Json::item($presenter->getData());
    }
}


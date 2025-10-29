<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\PaymentMethod\Handlers\DeletePaymentMethodHandler;
use Modules\Ecommerce\PaymentMethod\Handlers\UpdatePaymentMethodHandler;
use Modules\Ecommerce\PaymentMethod\Presenters\PaymentMethodPresenter;
use Modules\Ecommerce\PaymentMethod\Requests\CreatePaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Requests\DeletePaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Requests\GetPaymentMethodListRequest;
use Modules\Ecommerce\PaymentMethod\Requests\GetPaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Requests\UpdatePaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Services\PaymentMethodCRUDService;
use Modules\Ecommerce\PaymentMethod\Exports\PaymentMethodExport;
use Modules\Ecommerce\PaymentMethod\Requests\ExportPaymentMethodRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class PaymentMethodController extends Controller
{
    public function __construct(
        private PaymentMethodCRUDService $paymentMethodService,
        private UpdatePaymentMethodHandler $updatePaymentMethodHandler,
        private DeletePaymentMethodHandler $deletePaymentMethodHandler,
    ) {
    }

    public function index(GetPaymentMethodListRequest $request): JsonResponse
    {
        $mergedData = $this->paymentMethodService->getMergedPaymentMethods();

        return Json::items(
            PaymentMethodPresenter::collection($mergedData->toArray()),
            message: 'تم جلب طرق الدفع بنجاح'
        );
    }


    public function toggleStatus(GetPaymentMethodRequest $request): JsonResponse
    {
        try {
            $type = $request->route('type');
            $result = $this->paymentMethodService->togglePaymentMethodStatus($type);
            
            $presenter = new PaymentMethodPresenter($result['data']);
            
            return Json::item($presenter->getData(), message: $result['message']);
        } catch (\Exception $e) {
            return Json::error($e->getMessage());
        }
    }
}

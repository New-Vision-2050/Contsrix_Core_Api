<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Shared\PaymentMethodData\Requests\CreatePaymentMethodDataRequest;
use Modules\Shared\PaymentMethodData\Requests\DeletePaymentMethodDataRequest;
use Modules\Shared\PaymentMethodData\Requests\GetPaymentMethodDataListRequest;
use Modules\Shared\PaymentMethodData\Requests\GetPaymentMethodDataRequest;
use Modules\Shared\PaymentMethodData\Requests\UpdatePaymentMethodDataRequest;
use Modules\Shared\PaymentMethodData\Services\PaymentMethodDataCRUDService;
use Modules\Shared\PaymentMethodData\Presenters\PaymentMethodDataPresenter;
use Ramsey\Uuid\Uuid;

class PaymentMethodDataController extends Controller
{
    public function __construct(
        private readonly PaymentMethodDataCRUDService $paymentMethodDataService,
    ) {
    }

    public function index(GetPaymentMethodDataListRequest $request): JsonResponse
    {
        $list = $this->paymentMethodDataService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(
            PaymentMethodDataPresenter::collection($list['data']),
            $list['pagination'],
        );
    }

    public function store(CreatePaymentMethodDataRequest $request): JsonResponse
    {
        $dtos = $request->createDTOs();
        $createdMethods = $this->paymentMethodDataService->createMultiple($dtos);

        return Json::items(
            PaymentMethodDataPresenter::collection($createdMethods),
            message: 'تم إنشاء طرق الدفع بنجاح'
        );
    }
}

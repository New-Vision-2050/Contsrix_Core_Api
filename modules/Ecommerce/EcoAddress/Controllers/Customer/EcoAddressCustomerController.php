<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Controllers\Customer;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoAddress\Presenters\Customer\EcoAddressCustomerPresenter;
use Modules\Ecommerce\EcoAddress\Requests\Customer\CreateEcoAddressCustomerRequest;
use Modules\Ecommerce\EcoAddress\Requests\Customer\GetEcoAddressListCustomerRequest;
use Modules\Ecommerce\EcoAddress\Requests\Customer\GetEcoAddressCustomerRequest;
use Modules\Ecommerce\EcoAddress\Requests\Customer\UpdateEcoAddressCustomerRequest;
use Modules\Ecommerce\EcoAddress\Requests\Customer\DeleteEcoAddressCustomerRequest;
use Modules\Ecommerce\EcoAddress\Services\Customer\EcoAddressCustomerService;
use Ramsey\Uuid\Uuid;

class EcoAddressCustomerController extends Controller
{
    public function __construct(
        private EcoAddressCustomerService $ecoAddressService,
    ) {
    }

    public function index(GetEcoAddressListCustomerRequest $request): JsonResponse
    {
        $clientId = auth('sanctum')->user()->id ?? $request->get('client_id');
        
        $addresses = $this->ecoAddressService->getClientAddresses(
            Uuid::fromString($clientId),
            $request->get('type')
        );

        return Json::items(EcoAddressCustomerPresenter::collection($addresses));
    }

    public function show(GetEcoAddressCustomerRequest $request): JsonResponse
    {
        $address = $this->ecoAddressService->getClientAddress(
            Uuid::fromString($request->route('id')),
            auth('sanctum')->user()->id ?? $request->get('client_id')
        );

        $presenter = new EcoAddressCustomerPresenter($address);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoAddressCustomerRequest $request): JsonResponse
    {
        $address = $this->ecoAddressService->createAddress($request->toDTO());

        $presenter = new EcoAddressCustomerPresenter($address);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoAddressCustomerRequest $request): JsonResponse
    {
        $address = $this->ecoAddressService->updateAddress($request->toDTO());

        $presenter = new EcoAddressCustomerPresenter($address);

        return Json::item($presenter->getData());
    }

    public function destroy(DeleteEcoAddressCustomerRequest $request): JsonResponse
    {
        $this->ecoAddressService->deleteAddress(
            Uuid::fromString($request->route('id')),
            auth('sanctum')->user()->id ?? $request->get('client_id')
        );

        return Json::success();
    }

    public function setDefault(GetEcoAddressCustomerRequest $request): JsonResponse
    {
        $address = $this->ecoAddressService->setDefaultAddress(
            Uuid::fromString($request->route('id')),
            auth('sanctum')->user()->id ?? $request->get('client_id'),
            $request->get('type', 'shipping')
        );

        $presenter = new EcoAddressCustomerPresenter($address);

        return Json::item($presenter->getData());
    }

    public function getDefault(GetEcoAddressListCustomerRequest $request): JsonResponse
    {
        $clientId = auth('sanctum')->user()->id ?? $request->get('client_id');
        
        $address = $this->ecoAddressService->getDefaultAddress(
            Uuid::fromString($clientId),
            $request->get('type', 'shipping')
        );

        if (!$address) {
            return Json::item(null);
        }

        $presenter = new EcoAddressCustomerPresenter($address);

        return Json::item($presenter->getData());
    }
}

<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\ClientRequest\Models\ClientRequestService;
use Modules\ClientRequest\Presenters\ClientRequestServicePresenter;

class ClientRequestServiceController extends Controller
{
    public function index()
    {
        $services = ClientRequestService::all();

        return Json::items(ClientRequestServicePresenter::collection($services));
    }
}

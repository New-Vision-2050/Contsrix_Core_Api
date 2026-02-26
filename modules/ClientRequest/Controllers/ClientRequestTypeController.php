<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\ClientRequest\Models\ClientRequestType;
use Modules\ClientRequest\Presenters\ClientRequestTypePresenter;

class ClientRequestTypeController extends Controller
{
    public function index()
    {
        $types = ClientRequestType::all();

        return Json::items(ClientRequestTypePresenter::collection($types));
    }
}

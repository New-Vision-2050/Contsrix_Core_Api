<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Helpers\Json;
use Modules\ClientRequest\Models\ClientRequestService;

class ClientRequestServiceController extends Controller
{
    public function index()
    {
        $services = ClientRequestService::all();
        
        return Json::items($services);
    }
}

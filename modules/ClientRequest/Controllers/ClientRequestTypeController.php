<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Helpers\Json;
use Modules\ClientRequest\Models\ClientRequestType;

class ClientRequestTypeController extends Controller
{
    public function index()
    {
        $types = ClientRequestType::all();
        
        return Json::items($types);
    }
}

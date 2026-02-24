<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Helpers\Json;
use Modules\ClientRequest\Models\ClientRequestReceiverFrom;

class ClientRequestReceiverFromController extends Controller
{
    public function index()
    {
        $receivers = ClientRequestReceiverFrom::all();
        
        return Json::items($receivers);
    }
}

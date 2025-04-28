<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Language\Handlers\DeleteLanguageHandler;
use Modules\Shared\Media\Handlers\DeleteMediaHandler;
use Modules\Shared\Media\Requests\GetMediaRequest;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;

class MediaController extends Controller
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private DeleteMediaHandler $deleteMediaHandler
    ) {
    }


    public function delete(GetMediaRequest $request): JsonResponse
    {
        $this->deleteMediaHandler->handle($request->route('id'));

        return Json::deleted();
    }
}

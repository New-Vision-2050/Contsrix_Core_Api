<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\LoginWay;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Ramsey\Uuid\Uuid;

class MakeLoginWayDefaultRequest extends FormRequest
{
    public function rules(): array
    {
       return [
        ];
    }


}

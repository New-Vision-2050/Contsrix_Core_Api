<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;


class CustomException extends ExceptionHandler
{
    public function __construct($message = "A custom error occurred")
    {
        parent::__construct($message);
    }
}

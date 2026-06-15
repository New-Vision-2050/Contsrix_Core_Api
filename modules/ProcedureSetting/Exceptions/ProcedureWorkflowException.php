<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Exceptions;

use RuntimeException;

/**
 * Thrown by ProcedureWorkflowService when step-walking or authorization fails.
 *
 * Callers (entity services / controllers) should catch this alongside their own
 * domain exceptions and surface the message + HTTP code to the API.
 */
final class ProcedureWorkflowException extends RuntimeException
{
    public static function stepNotFound(): self
    {
        return new self(__('The configured procedure step could not be found.'), 422);
    }

    public static function notAuthorized(): self
    {
        return new self(__('You are not an authorized action-taker for the current approval step.'), 403);
    }

    public static function noStepsConfigured(): self
    {
        return new self(__('The procedure setting has no steps configured.'), 422);
    }

    public static function noActiveStep(): self
    {
        return new self(__('This entity has no active procedure step to act on.'), 422);
    }

    public static function settingNotFound(): self
    {
        return new self(__('The procedure setting could not be found.'), 404);
    }
}

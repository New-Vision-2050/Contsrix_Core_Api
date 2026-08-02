<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Exceptions;

use RuntimeException;
use Throwable;

class PCloudApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $method = null,
        public readonly ?int $pcloudResult = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $pcloudResult ?? 0, $previous);
    }
}

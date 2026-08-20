<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

final class InvalidUdsExcelHeaderException extends \RuntimeException
{
    /**
     * @param  list<string>  $mismatches
     */
    public function __construct(
        string $message,
        private readonly array $mismatches = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return list<string>
     */
    public function mismatches(): array
    {
        return $this->mismatches;
    }
}

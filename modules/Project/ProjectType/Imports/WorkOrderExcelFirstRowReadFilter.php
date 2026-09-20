<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class WorkOrderExcelFirstRowReadFilter implements IReadFilter
{
    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return (int) $row === 1;
    }
}

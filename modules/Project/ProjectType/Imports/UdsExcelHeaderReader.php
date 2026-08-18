<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class UdsExcelHeaderReader
{
    /**
     * @return list<string>
     */
    public function readFirstRow(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new UdsExcelFirstRowReadFilter());

        $spreadsheet = $reader->load($absolutePath);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $row = $sheet->rangeToArray('A1:AL1', null, true, false)[0] ?? [];

            return array_map(
                static fn (mixed $value): string => $value === null ? '' : (string) $value,
                array_values($row),
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }
}

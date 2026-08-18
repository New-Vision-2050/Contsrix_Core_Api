<?php

declare(strict_types=1);

use Modules\Project\ProjectType\Imports\UdsExcelOfficialHeader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__ . '/../../../../../vendor/autoload.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('UDS');
$sheet->setRightToLeft(true);
$sheet->fromArray(UdsExcelOfficialHeader::COLUMNS, null, 'A1');
$sheet->freezePane('A2');
$sheet->getStyle('A1:AL1')->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'wrapText' => true,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D9E2F3'],
    ],
]);

foreach (range(1, UdsExcelOfficialHeader::COLUMN_COUNT) as $columnIndex) {
    $sheet->getColumnDimensionByColumn($columnIndex)->setWidth(22);
}

$sheet->getRowDimension(1)->setRowHeight(30);

$destination = __DIR__ . DIRECTORY_SEPARATOR . 'uds-excel-template.xlsx';
$directory = dirname($destination);
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}

$writer = new Xlsx($spreadsheet);
$writer->save($destination);

echo $destination . PHP_EOL;

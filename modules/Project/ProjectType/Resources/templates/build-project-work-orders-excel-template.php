<?php

declare(strict_types=1);

use Modules\Project\ProjectType\Imports\WorkOrderExcelOfficialHeader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__.'/../../../../../vendor/autoload.php';

$spreadsheet = new Spreadsheet;
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Project Work Orders');
$sheet->setRightToLeft(true);
$sheet->fromArray(WorkOrderExcelOfficialHeader::COLUMNS, null, 'A1');
$sheet->freezePane('A2');
$sheet->getStyle('A1:M1')->applyFromArray([
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

foreach (range(1, WorkOrderExcelOfficialHeader::COLUMN_COUNT) as $columnIndex) {
    $sheet->getColumnDimensionByColumn($columnIndex)->setWidth(24);
}

$sheet->getRowDimension(1)->setRowHeight(42);

$destination = __DIR__.DIRECTORY_SEPARATOR.WorkOrderExcelOfficialHeader::DOWNLOAD_NAME;
$writer = new Xlsx($spreadsheet);
$writer->save($destination);

echo $destination.PHP_EOL;

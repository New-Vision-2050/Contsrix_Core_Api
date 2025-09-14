<?php

declare(strict_types=1);

namespace App\Traits;

use Maatwebsite\Excel\Facades\Excel;

trait HasExportController
{
    /**
     * Export data to file
     * This method should be added to controllers that need export functionality
     *
     * Usage in controller:
     * public function export(Export{Model}Request $request)
     * {
     *     return $this->handleExport($request, {Model}Export::class, '{model_name}');
     * }
     */
    protected function handleExport($request, string $exportClass, string $fileName)
    {
        $format = $request->get('format', 'xlsx');
        $fullFileName = $fileName . '.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new $exportClass($this->service, $filters), $fullFileName);
    }
}

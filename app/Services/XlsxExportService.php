<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxExportService
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $columns
     * @param array<string, string> $headerLabels
     * @param array<int, string> $dateColumns
     */
    public function stream(string $filename, array $rows, array $columns, array $headerLabels = [], array $dateColumns = []): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $columnCount = count($columns);
        if ($columnCount === 0) {
            throw new \RuntimeException('No hay columnas disponibles para exportar.');
        }

        foreach ($columns as $index => $columnKey) {
            $columnNumber = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($columnNumber);
            $headerText = $headerLabels[$columnKey] ?? $columnKey;
            $sheet->setCellValue($columnLetter . '1', $headerText);
        }

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($columns as $index => $columnKey) {
                $columnNumber = $index + 1;
                $columnLetter = Coordinate::stringFromColumnIndex($columnNumber);
                $cellAddress = $columnLetter . $rowNumber;
                $rawValue = $row[$columnKey] ?? null;

                if ($rawValue === null) {
                    $sheet->setCellValue($cellAddress, '');
                    continue;
                }

                $value = is_scalar($rawValue) ? (string) $rawValue : '';

                if (in_array($columnKey, $dateColumns, true)) {
                    $timestamp = strtotime($value);
                    if ($timestamp !== false) {
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp);
                        $sheet->setCellValue($cellAddress, $excelDate);

                        $formatCode = strpos($value, ':') !== false
                            ? NumberFormat::FORMAT_DATE_DATETIME
                            : 'yyyy-mm-dd';

                        $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode($formatCode);
                        continue;
                    }
                }

                $sheet->setCellValueExplicit($cellAddress, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            $rowNumber++;
        }

        $lastColumnLetter = Coordinate::stringFromColumnIndex($columnCount);
        $lastDataRow = max(1, $rowNumber - 1);
        $sheet->setAutoFilter('A1:' . $lastColumnLetter . $lastDataRow);

        for ($i = 1; $i <= $columnCount; $i++) {
            $letter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        exit;
    }
}
